<?php

namespace App\Http\Controllers;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Enums\VehiclePosition;
use App\Models\Accommodation;
use App\Models\Employee;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Models\Project;
use App\Models\Role;
use App\Models\TransportCost;
use App\Models\Vehicle;
use App\Services\AccommodationAssignmentService;
use App\Services\AssignmentQueryService;
use App\Services\DepartureService;
use App\Services\ProjectAssignmentService;
use App\Services\VehicleAssignmentService;
use App\Services\VehicleValidationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartureController extends Controller
{
    public function __construct(
        protected DepartureService $departureService,
        protected AssignmentQueryService $assignmentQueryService,
        protected ProjectAssignmentService $projectAssignmentService,
        protected VehicleAssignmentService $vehicleAssignmentService,
        protected AccommodationAssignmentService $accommodationAssignmentService,
        protected VehicleValidationService $vehicleValidationService
    ) {}

    /**
     * Display a listing of departures.
     */
    public function index(Request $request): View
    {
        $sort = (string) $request->query('sort', 'id');
        $dir = strtolower((string) $request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $employeeSearch = trim((string) $request->query('employee_search', ''));
        $vehicleFilter = $request->query('vehicle_id'); // int|string|null; supports "none"
        $transport = $request->query('transport'); // "vehicle"|"no_vehicle"|null

        $allowedSorts = ['id', 'event_date', 'created_at'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        $query = LogisticsEvent::where('type', LogisticsEventType::DEPARTURE)
            ->with(['vehicle', 'fromLocation', 'toLocation', 'creator', 'participants.employee'])
            ->when($employeeSearch !== '', function ($q) use ($employeeSearch) {
                $s = mb_strtolower($employeeSearch);
                $q->whereHas('participants.employee', function ($e) use ($s) {
                    $e->whereRaw('LOWER(CONCAT(first_name, " ", last_name)) LIKE ?', ['%'.$s.'%'])
                        ->orWhereRaw('LOWER(CONCAT(last_name, " ", first_name)) LIKE ?', ['%'.$s.'%'])
                        ->orWhereRaw('LOWER(first_name) LIKE ?', ['%'.$s.'%'])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', ['%'.$s.'%'])
                        ->orWhereRaw('LOWER(phone) LIKE ?', ['%'.$s.'%']);
                });
            })
            ->when($transport === 'vehicle', fn ($q) => $q->whereNotNull('vehicle_id'))
            ->when($transport === 'no_vehicle', fn ($q) => $q->whereNull('vehicle_id'))
            ->when($vehicleFilter === 'none', fn ($q) => $q->whereNull('vehicle_id'))
            ->when(is_numeric($vehicleFilter), fn ($q) => $q->where('vehicle_id', (int) $vehicleFilter))
            ->orderBy($sort, $dir);

        $departures = $query->paginate(20)->withQueryString();

        $vehicles = Vehicle::where('type', 'company_vehicle')->orderBy('registration_number')->get();

        return view('departures.index', compact('departures', 'sort', 'dir', 'vehicles', 'employeeSearch', 'vehicleFilter', 'transport'));
    }

    /**
     * Display the specified departure.
     */
    public function show(LogisticsEvent $departure): View
    {
        if ($departure->type !== LogisticsEventType::DEPARTURE) {
            abort(404);
        }

        $departure->load([
            'vehicle',
            'fromLocation',
            'toLocation',
            'creator',
            'participants.employee',
            'projectAssignments.project.location',
            'vehicleAssignments.vehicle',
            'accommodationAssignments.employee',
            'accommodationAssignments.accommodation',
            'transportCosts.creator',
        ]);

        // If this was a public transport departure, it may have an auto-created transfer
        $transfer = $this->departureService->findLinkedAirportTransfer($departure, false);

        $transferDrivingStops = collect();
        $transferTicketFlightLine = null;
        $transferEndAirportLocation = null;
        if ($transfer) {
            $transfer->load([
                'vehicle',
                'fromLocation',
                'toLocation',
                'creator',
                'participants.employee',
                'driverAdjustments.employee',
                'driverAdjustments.payroll',
            ]);

            // Pełna kolejność przystanków + notatki (loc:) z zapisu wyjazdu — ten sam model co „Przebieg trasy”
            $transferDrivingStops = $departure->getRouteStopsForDetailView();

            $firstTicket = $departure->transportCosts->where('cost_type', 'ticket')->first();
            if ($firstTicket && $firstTicket->notes && preg_match('/Lotnisko:\s*(.+?)\s*→\s*(.+?)(?:\s*\||$)/u', $firstTicket->notes, $m)) {
                $transferTicketFlightLine = trim($m[1]).' → '.trim($m[2]);
                $arrivalAirportName = trim($m[2]);
                $transferEndAirportLocation = Location::query()
                    ->where(function ($q) use ($arrivalAirportName) {
                        $q->where('name', $arrivalAirportName)
                            ->orWhere('name', 'like', '%'.addcslashes($arrivalAirportName, '%_\\').'%');
                    })
                    ->first();
            }
        }

        return view('departures.show', [
            'departure' => $departure,
            'transfer' => $transfer,
            'transferDrivingStops' => $transferDrivingStops,
            'transferTicketFlightLine' => $transferTicketFlightLine,
            'transferEndAirportLocation' => $transferEndAirportLocation,
        ]);
    }

    /**
     * Prepare cancellation - show what will be affected.
     */
    public function prepareCancellation(LogisticsEvent $departure)
    {
        if ($departure->type !== LogisticsEventType::DEPARTURE) {
            abort(404);
        }

        // Only allow cancellation preparation if status is PLANNED or COMPLETED
        if (! in_array($departure->status, [\App\Enums\LogisticsEventStatus::PLANNED, \App\Enums\LogisticsEventStatus::COMPLETED])) {
            return redirect()
                ->route('departures.show', $departure)
                ->with('error', 'Można anulować tylko wyjazdy ze statusem "Oczekuje na przypisanie" lub "Przypisany".');
        }

        // Find all affected assignments - SIMPLE! Use relationships
        $affectedProjectAssignments = $departure->projectAssignments()
            ->with(['employee', 'project.location', 'role'])
            ->get();

        $affectedVehicleAssignments = $departure->vehicleAssignments()
            ->with(['employee', 'vehicle'])
            ->get();

        $affectedAccommodationAssignments = $departure->accommodationAssignments()
            ->with(['employee', 'accommodation'])
            ->get();

        $departure->loadMissing(['fromLocation', 'toLocation', 'transportCosts']);

        $linkedTransfer = $this->departureService->findLinkedAirportTransfer($departure, true);
        if ($linkedTransfer) {
            $linkedTransfer->loadMissing([
                'fromLocation',
                'toLocation',
                'vehicle',
                'transportCosts',
                'driverAdjustments.employee',
                'driverAdjustments.payroll',
            ]);
        }

        $hasAssignments = $affectedProjectAssignments->isNotEmpty()
            || $affectedVehicleAssignments->isNotEmpty()
            || $affectedAccommodationAssignments->isNotEmpty();

        $allTransportCosts = $departure->transportCosts;
        if ($linkedTransfer) {
            $allTransportCosts = $allTransportCosts->concat($linkedTransfer->transportCosts);
        }

        $fuelCosts = $allTransportCosts->where('cost_type', 'fuel')->values();
        $otherCosts = $allTransportCosts->whereIn('cost_type', ['parking', 'toll', 'other'])->values();
        $ticketRemovalRows = collect();
        foreach ($departure->transportCosts->where('cost_type', 'ticket') as $tc) {
            $ticketRemovalRows->push(['cost' => $tc, 'eventLabel' => 'Wyjazd']);
        }
        if ($linkedTransfer) {
            foreach ($linkedTransfer->transportCosts->where('cost_type', 'ticket') as $tc) {
                $ticketRemovalRows->push(['cost' => $tc, 'eventLabel' => 'Transfer']);
            }
        }

        $transferRewardsRemovable = $linkedTransfer
            ? $linkedTransfer->driverAdjustments->whereNull('payroll_id')->values()
            : collect();
        $transferRewardsLocked = $linkedTransfer
            ? $linkedTransfer->driverAdjustments->whereNotNull('payroll_id')->values()
            : collect();

        $fuelCostsSummary = $fuelCosts->isNotEmpty() ? $this->summarizeMoneyByCurrency($fuelCosts) : null;
        $otherCostsSummary = $otherCosts->isNotEmpty() ? $this->summarizeMoneyByCurrency($otherCosts) : null;
        $transferRewardSummary = $transferRewardsRemovable->isNotEmpty()
            ? $this->summarizeMoneyByCurrency($transferRewardsRemovable)
            : null;

        $showCostRemovalChoices = $fuelCosts->isNotEmpty()
            || $otherCosts->isNotEmpty()
            || $ticketRemovalRows->isNotEmpty()
            || $transferRewardsRemovable->isNotEmpty()
            || $transferRewardsLocked->isNotEmpty();

        $cancellationHasSideEffects = $hasAssignments
            || $linkedTransfer !== null
            || $departure->transportCosts->isNotEmpty()
            || ($linkedTransfer && $linkedTransfer->transportCosts->isNotEmpty())
            || ($linkedTransfer && $linkedTransfer->driverAdjustments->isNotEmpty());

        $affectedEmployeeIds = $affectedProjectAssignments->pluck('employee_id')
            ->concat($affectedVehicleAssignments->pluck('employee_id'))
            ->concat($affectedAccommodationAssignments->pluck('employee_id'))
            ->unique()
            ->values();

        $cancellationPreviewRows = $affectedEmployeeIds
            ->map(function ($employeeId) use (
                $affectedProjectAssignments,
                $affectedVehicleAssignments,
                $affectedAccommodationAssignments
            ) {
                $projectAssignment = $affectedProjectAssignments->firstWhere('employee_id', $employeeId);
                $vehicleAssignment = $affectedVehicleAssignments->firstWhere('employee_id', $employeeId);
                $accommodationAssignment = $affectedAccommodationAssignments->firstWhere('employee_id', $employeeId);

                $employee = $projectAssignment?->employee
                    ?? $vehicleAssignment?->employee
                    ?? $accommodationAssignment?->employee;

                return [
                    'employee' => $employee,
                    'project_assignment' => $projectAssignment,
                    'vehicle_assignment' => $vehicleAssignment,
                    'accommodation_assignment' => $accommodationAssignment,
                ];
            })
            ->filter(fn (array $row) => $row['employee'] !== null)
            ->sortBy(fn (array $row) => $row['employee']->full_name)
            ->values();

        return view('departures.prepare-cancellation', [
            'departure' => $departure,
            'affectedProjectAssignments' => $affectedProjectAssignments,
            'affectedVehicleAssignments' => $affectedVehicleAssignments,
            'affectedAccommodationAssignments' => $affectedAccommodationAssignments,
            'cancellationPreviewRows' => $cancellationPreviewRows,
            'linkedTransfer' => $linkedTransfer,
            'hasAssignments' => $hasAssignments,
            'cancellationHasSideEffects' => $cancellationHasSideEffects,
            'showCostRemovalChoices' => $showCostRemovalChoices,
            'fuelCostsSummary' => $fuelCostsSummary,
            'otherCostsSummary' => $otherCostsSummary,
            'transferRewardSummary' => $transferRewardSummary,
            'ticketRemovalRows' => $ticketRemovalRows,
            'transferRewardsLocked' => $transferRewardsLocked,
        ]);
    }

    /**
     * Cancel a departure.
     *
     * Cancels the departure and optionally cancels related project assignments.
     * Requires accept_consequences checkbox to be checked.
     */
    public function cancel(Request $request, LogisticsEvent $departure): RedirectResponse
    {
        if ($departure->type !== LogisticsEventType::DEPARTURE) {
            abort(404);
        }

        // Only allow cancellation if status is PLANNED or COMPLETED
        if (! in_array($departure->status, [\App\Enums\LogisticsEventStatus::PLANNED, \App\Enums\LogisticsEventStatus::COMPLETED])) {
            return redirect()
                ->route('departures.show', $departure)
                ->with('error', 'Można anulować tylko wyjazdy ze statusem "Oczekuje na przypisanie" lub "Przypisany".');
        }

        $requiresConsequenceAccept = $departure->projectAssignments()->exists()
            || $departure->vehicleAssignments()->exists()
            || $departure->accommodationAssignments()->exists()
            || $departure->transportCosts()->exists()
            || $this->departureService->findLinkedAirportTransfer($departure, true) !== null;

        $eventIdsForTickets = [$departure->id];
        if ($transferForValidation = $this->departureService->findLinkedAirportTransfer($departure, true)) {
            $eventIdsForTickets[] = $transferForValidation->id;
        }

        $request->validate([
            'accept_consequences' => ($requiresConsequenceAccept ? 'required' : 'sometimes').'|accepted',
            'remove_fuel' => 'sometimes|boolean',
            'remove_other_costs' => 'sometimes|boolean',
            'remove_transfer_reward' => 'sometimes|boolean',
            'remove_ticket_ids' => 'sometimes|array',
            'remove_ticket_ids.*' => [
                'integer',
                Rule::exists('transport_costs', 'id')->where(function ($q) use ($eventIdsForTickets) {
                    $q->whereIn('logistics_event_id', $eventIdsForTickets)
                        ->where('cost_type', 'ticket');
                }),
            ],
        ], [
            'accept_consequences.accepted' => 'Musisz zaakceptować konsekwencje anulacji wyjazdu.',
            'accept_consequences.required' => 'Musisz zaakceptować konsekwencje anulacji wyjazdu.',
        ]);

        try {
            $costSelection = [
                'remove_fuel' => $request->boolean('remove_fuel'),
                'remove_other_costs' => $request->boolean('remove_other_costs'),
                'remove_transfer_reward' => $request->boolean('remove_transfer_reward'),
                'remove_ticket_ids' => array_map('intval', (array) $request->input('remove_ticket_ids', [])),
            ];

            $cancelledCounts = DB::transaction(function () use ($departure, $costSelection) {
                // Get participants before cancellation
                $participants = $departure->participants()->with('employee')->get();

                // SIMPLE! Use direct relationships - we know exactly which assignments belong to this departure
                $projectAssignments = $departure->projectAssignments()->get();
                $vehicleAssignments = $departure->vehicleAssignments()->get();
                $accommodationAssignments = $departure->accommodationAssignments()->get();

                $cascade = $this->departureService->cancelDepartureLinkedTransferAndCosts(
                    $departure,
                    $costSelection
                );

                // Cancel the departure
                $departure->update([
                    'status' => \App\Enums\LogisticsEventStatus::CANCELLED,
                ]);

                // Delete related assignments (physical deletion)
                $cancelledProjectCount = $projectAssignments->each->delete()->count();
                $cancelledVehicleCount = $vehicleAssignments->each->delete()->count();
                $cancelledAccommodationCount = $accommodationAssignments->each->delete()->count();

                // Update outside_base flag for all participants
                $locationTracker = app(\App\Services\LocationTrackingService::class);
                foreach ($participants as $participant) {
                    $locationTracker->getLocationStatus($participant->employee, now());
                }

                // Log the cancellation
                Log::info('Departure cancelled - related assignments deleted', [
                    'departure_id' => $departure->id,
                    'deleted_project_assignments' => $cancelledProjectCount,
                    'deleted_vehicle_assignments' => $cancelledVehicleCount,
                    'deleted_accommodation_assignments' => $cancelledAccommodationCount,
                    'participants_updated' => $participants->count(),
                ]);

                return [
                    'project' => $cancelledProjectCount,
                    'vehicle' => $cancelledVehicleCount,
                    'accommodation' => $cancelledAccommodationCount,
                    'cascade' => $cascade,
                ];
            });

            $totalCancelled = $cancelledCounts['project'] + $cancelledCounts['vehicle'] + $cancelledCounts['accommodation'];
            $cascade = $cancelledCounts['cascade'];

            $message = 'Wyjazd został anulowany.';
            if ($totalCancelled > 0) {
                $message .= " Usunięto {$totalCancelled} powiązanych przypisań";
                $details = [];
                if ($cancelledCounts['project'] > 0) {
                    $details[] = "{$cancelledCounts['project']} projektów";
                }
                if ($cancelledCounts['vehicle'] > 0) {
                    $details[] = "{$cancelledCounts['vehicle']} pojazdów";
                }
                if ($cancelledCounts['accommodation'] > 0) {
                    $details[] = "{$cancelledCounts['accommodation']} domów";
                }
                if (! empty($details)) {
                    $message .= ' ('.implode(', ', $details).')';
                }
                $message .= '.';
            }

            if ($cascade['transfer_cancelled']) {
                $message .= ' Powiązany transfer został anulowany.';
            }
            if ($cascade['transport_costs_deleted'] > 0) {
                $message .= ' Usunięto '.$cascade['transport_costs_deleted'].' zapisów kosztów transportu.';
            }
            if ($cascade['adjustments_deleted'] > 0) {
                $message .= ' Usunięto '.$cascade['adjustments_deleted'].' korekt wynagrodzenia powiązanych z transferem.';
            }
            if ($cascade['adjustments_skipped_payroll'] > 0) {
                $message .= ' Uwaga: '.$cascade['adjustments_skipped_payroll'].' korekt jest już w rozliczeniu płac — nie usunięto ich automatycznie.';
            }

            return redirect()
                ->route('departures.show', $departure)
                ->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Error cancelling departure', [
                'departure_id' => $departure->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('departures.show', $departure)
                ->with('error', 'Wystąpił błąd podczas anulowania wyjazdu: '.$e->getMessage());
        }
    }

    /**
     * Show the V2 form for creating a new departure.
     * Uses Livewire component for all form logic.
     */
    public function createV2(Request $request): View
    {
        $baseLocation = Location::getBase();

        return view('departures.create-v2', compact('baseLocation'));
    }

    /**
     * Store the final V2 departure with all assignments.
     * Receives data from Livewire component via request parameters.
     */
    public function storeV2(Request $request): RedirectResponse
    {
        // Get data from session (set by Livewire component) or from request (fallback)
        $sessionData = session('departure_v2_data', []);

        // Merge session data with request data (request takes precedence)
        $data = array_merge($sessionData, $request->all());

        // Normalize empty strings to null for integer/nullable fields
        // (session data bypasses ConvertEmptyStringsToNull middleware)
        foreach (['vehicle_id'] as $field) {
            if (isset($data[$field]) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        // Validate data
        $validator = validator($data, [
            'departure_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:departure_date',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'assignments' => 'nullable|array',
            'assignment_ranges' => 'nullable|array',
            'vehicle_seats' => 'nullable|array',
            'accommodation_assignments' => 'nullable|array',
            'vehicle_assignments' => 'nullable|array',
            'route_data' => 'nullable|array',
            'route_data.route_distance' => 'nullable|numeric|min:0',
            'route_data.route_duration' => 'nullable|numeric|min:0', // float from OpenRouteService
            'route_data.route_waypoints' => 'nullable|array',
            'route_data.location_stop_notes' => 'nullable|array',
            'ticket_costs_per_employee' => 'nullable|array',
            'transfer_config' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            $errorMessages = collect($validator->errors()->all())->implode(' | ');

            return redirect()
                ->route('departures.create-v2')
                ->with('error', 'Błąd walidacji danych wyjazdu: '.$errorMessages);
        }

        $validated = $validator->validated();

        if (empty($validated['vehicle_id'])) {
            $ticketCostsPerEmployee = $validated['ticket_costs_per_employee'] ?? [];

            if (empty($ticketCostsPerEmployee) || ! is_array($ticketCostsPerEmployee)) {
                return redirect()
                    ->route('departures.create-v2')
                    ->with('error', 'Przy wyjeździe bez auta musisz uzupełnić koszty biletów dla każdej osoby.');
            }
        }

        // Transform assignments from nested structure to flat array (if needed)
        $assignmentsData = $validated['assignments'] ?? [];

        // Handle case where assignments might be a JSON string
        if (is_string($assignmentsData)) {
            $assignmentsData = json_decode($assignmentsData, true) ?? [];
        }

        // Ensure it's an array
        if (! is_array($assignmentsData)) {
            $assignmentsData = [];
        }

        $flatAssignments = [];

        foreach ($assignmentsData as $dayKey => $projects) {
            // Extract day number from "day_1", "day_2", etc.
            $dayNumber = (int) str_replace('day_', '', $dayKey);

            if (! is_array($projects)) {
                continue;
            }

            foreach ($projects as $projectId => $roles) {
                if (! is_array($roles)) {
                    continue;
                }

                foreach ($roles as $roleId => $employeeIds) {
                    if (! is_array($employeeIds)) {
                        continue;
                    }

                    foreach ($employeeIds as $employeeId) {
                        $flatAssignments[] = [
                            'employee_id' => $employeeId,
                            'project_id' => $projectId,
                            'role_id' => $roleId,
                            'day' => $dayNumber,
                        ];
                    }
                }
            }
        }

        // Get assignment ranges (primary way - from Livewire)
        $assignmentRanges = $validated['assignment_ranges'] ?? [];

        // Update validated data
        $validated['assignments'] = $flatAssignments;

        // Basic validation
        if (empty($validated['departure_date']) || empty($validated['end_date'])) {
            return redirect()
                ->route('departures.create-v2')
                ->with('error', 'Brak wymaganych dat wyjazdu.');
        }

        // Validate assignment ranges separately
        if (! empty($assignmentRanges)) {
            foreach ($assignmentRanges as $key => $range) {
                if (empty($range['employee_id']) || empty($range['project_id']) || empty($range['role_id']) || empty($range['start_date'])) {
                    continue; // Skip invalid ranges
                }

                $startDate = Carbon::parse($range['start_date']);
                $endDate = ! empty($range['end_date']) ? Carbon::parse($range['end_date']) : $startDate;

                if ($endDate->lt($startDate)) {
                    return redirect()
                        ->back()
                        ->withInput()
                        ->with('error', "Nieprawidłowy zakres dat dla przypisania: {$range['start_date']} - {$range['end_date']}");
                }
            }
        }

        // Validate that we have at least some assignments (either flat or ranges)
        if (empty($flatAssignments) && empty($assignmentRanges)) {
            return redirect()
                ->route('departures.create-v2')
                ->with('error', 'Musisz przypisać przynajmniej jednego pracownika do projektu.');
        }

        DB::beginTransaction();
        try {
            $departureDate = Carbon::parse($validated['departure_date']);
            $endDate = Carbon::parse($validated['end_date']);
            $arrivalDate = $endDate;

            // Determine destination location.
            // In V2, the destination (to_location_id) is the location of the LAST route waypoint (accommodation).
            // We do NOT require projects to be in the same location.
            $destinationLocationId = null;

            // Prepare route data if available (acc: / loc: + notatki przy lokalizacjach dodanych ręcznie)
            $routeData = $validated['route_data'] ?? null;
            $normalizedRouteWaypoints = [];

            if ($routeData && ! empty($routeData['route_waypoints'])) {
                $waypoints = $routeData['route_waypoints'];
                if (is_string($waypoints)) {
                    $waypoints = json_decode($waypoints, true);
                }
                if (is_array($waypoints)) {
                    $normalizedRouteWaypoints = LogisticsEvent::normalizeRouteWaypointsFromPayload($waypoints);
                }
            }

            $locationStopNotesPayload = LogisticsEvent::sanitizeLocationStopNotes(
                isset($routeData['location_stop_notes']) && is_array($routeData['location_stop_notes'])
                    ? $routeData['location_stop_notes']
                    : null,
                $normalizedRouteWaypoints
            );

            if ($normalizedRouteWaypoints !== []) {
                $lastKey = end($normalizedRouteWaypoints);
                $lastParsed = LogisticsEvent::parseRouteWaypointKey($lastKey);

                if ($lastParsed && $lastParsed['type'] === 'loc') {
                    $lastLocation = Location::find($lastParsed['id']);
                    if (! $lastLocation) {
                        DB::rollBack();

                        return redirect()
                            ->back()
                            ->withInput()
                            ->with('error', 'Nie znaleziono lokalizacji będącej ostatnim przystankiem trasy.');
                    }
                    $destinationLocationId = $lastLocation->id;
                } elseif ($lastParsed && $lastParsed['type'] === 'acc') {
                    $lastAccommodationId = $lastParsed['id'];
                    $lastAccommodation = Accommodation::find($lastAccommodationId);

                    if (! $lastAccommodation) {
                        DB::rollBack();

                        return redirect()
                            ->back()
                            ->withInput()
                            ->with('error', 'Nie znaleziono ostatniego przystanku trasy (akomodacji).');
                    }

                    $existingLocation = Location::query()
                        ->where('address', $lastAccommodation->address)
                        ->where('city', $lastAccommodation->city)
                        ->where('postal_code', $lastAccommodation->postal_code)
                        ->where('country', $lastAccommodation->country)
                        ->first();

                    if ($existingLocation) {
                        $destinationLocationId = $existingLocation->id;
                    } else {
                        $created = Location::create([
                            'name' => $lastAccommodation->name,
                            'address' => $lastAccommodation->address,
                            'city' => $lastAccommodation->city,
                            'postal_code' => $lastAccommodation->postal_code,
                            'country' => $lastAccommodation->country,
                            'latitude' => $lastAccommodation->latitude,
                            'longitude' => $lastAccommodation->longitude,
                            'is_base' => false,
                        ]);

                        $destinationLocationId = $created->id;
                    }
                }
            }

            // Fallback: if there is no route (or no waypoints), try to use any project location (without enforcing uniqueness).
            if (! $destinationLocationId) {
                $projectIds = collect($validated['assignments'])->pluck('project_id')->unique();
                foreach ($assignmentRanges as $range) {
                    if (! empty($range['project_id'])) {
                        $projectIds->push($range['project_id']);
                    }
                }
                $projectIds = $projectIds->unique();

                $destinationLocationId = Project::whereIn('id', $projectIds)->pluck('location_id')->filter()->first();
            }

            if (! $destinationLocationId) {
                DB::rollBack();

                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Nie można określić lokalizacji docelowej. Uzupełnij trasę (krok 4) albo upewnij się, że projekty mają przypisaną lokalizację.');
            }

            $baseLocation = Location::getBase();

            if (! $baseLocation) {
                DB::rollBack();

                return redirect()
                    ->route('departures.create-v2')
                    ->with('error', 'Brak skonfigurowanej lokalizacji bazy. Przejdź do Lokalizacje i oznacz jedną jako bazę (is_base = true).');
            }

            // Create departure (location_stop_notes wymaga migracji — patrz add_location_stop_notes_to_logistics_events)
            $departureAttributes = [
                'type' => LogisticsEventType::DEPARTURE,
                'event_date' => $departureDate,
                'end_date' => $endDate,
                'from_location_id' => $baseLocation->id,
                'to_location_id' => $destinationLocationId,
                'vehicle_id' => $validated['vehicle_id'] ?? null,
                'status' => LogisticsEventStatus::COMPLETED,
                'notes' => null,
                'has_transport' => ! empty($validated['vehicle_id']),
                'created_by' => auth()->id() ?? 1,
                // Route data
                'route_distance' => $routeData['route_distance'] ?? null,
                'route_duration' => isset($routeData['route_duration']) ? (int) $routeData['route_duration'] : null,
                'route_waypoints' => $normalizedRouteWaypoints !== [] ? $normalizedRouteWaypoints : null,
                'destination_stop_location' => null,
            ];
            if (Schema::hasColumn('logistics_events', 'location_stop_notes')) {
                $departureAttributes['location_stop_notes'] = $locationStopNotesPayload;
            } elseif ($locationStopNotesPayload !== null && $locationStopNotesPayload !== []) {
                Log::warning('location_stop_notes pominięte przy zapisie wyjazdu — brak kolumny w bazie. Uruchom migracje.', [
                    'keys' => array_keys($locationStopNotesPayload),
                ]);
            }

            $departure = LogisticsEvent::create($departureAttributes);

            // Get unique employee IDs from both assignments and ranges
            $employeeIds = collect($validated['assignments'])->pluck('employee_id')->unique();
            foreach ($assignmentRanges as $range) {
                if (! empty($range['employee_id'])) {
                    $employeeIds->push($range['employee_id']);
                }
            }
            $employeeIds = $employeeIds->unique();

            if (empty($validated['vehicle_id'])) {
                $ticketCostsPerEmployee = $validated['ticket_costs_per_employee'] ?? [];
                foreach ($employeeIds as $employeeId) {
                    $costData = $ticketCostsPerEmployee[$employeeId] ?? null;
                    if (
                        ! $costData
                        || empty($costData['amount'])
                        || empty($costData['currency'])
                        || empty($costData['start_airport_location_id'])
                        || empty($costData['end_airport_location_id'])
                    ) {
                        DB::rollBack();

                        return redirect()
                            ->route('departures.create-v2')
                            ->with('error', 'Uzupełnij koszt biletu, walutę oraz lotnisko startowe i docelowe dla każdej osoby.');
                    }
                }
            }

            // Add participants
            foreach ($employeeIds as $employeeId) {
                $departure->participants()->create([
                    'employee_id' => $employeeId,
                ]);
            }

            if (empty($validated['vehicle_id']) && $employeeIds->isNotEmpty()) {
                $employeeNames = Employee::whereIn('id', $employeeIds)->get()->pluck('full_name', 'id');
                $ticketCostsPerEmployee = $validated['ticket_costs_per_employee'] ?? [];
                $destinationStopLocationName = Location::whereKey($destinationLocationId)->value('name');
                $airportLocationIds = collect($ticketCostsPerEmployee)
                    ->flatMap(function ($row) {
                        $start = $row['start_airport_location_id'] ?? null;
                        $end = $row['end_airport_location_id'] ?? null;

                        return array_filter([(int) $start, (int) $end]);
                    })
                    ->unique()
                    ->values();
                $airportNames = Location::whereIn('id', $airportLocationIds)->pluck('name', 'id');

                foreach ($employeeIds as $employeeId) {
                    $employeeName = $employeeNames[$employeeId] ?? ('ID: '.$employeeId);
                    $costData = $ticketCostsPerEmployee[$employeeId] ?? [];
                    $startAirportName = null;
                    $endAirportName = null;
                    if (! empty($costData['start_airport_location_id'])) {
                        $startAirportName = $airportNames[(int) $costData['start_airport_location_id']] ?? null;
                    }
                    if (! empty($costData['end_airport_location_id'])) {
                        $endAirportName = $airportNames[(int) $costData['end_airport_location_id']] ?? null;
                    }
                    $airportText = null;
                    if ($startAirportName && $endAirportName) {
                        $airportText = 'Lotnisko: '.$startAirportName.' → '.$endAirportName;
                    }

                    TransportCost::create([
                        'logistics_event_id' => $departure->id,
                        'vehicle_id' => null,
                        'transport_id' => null,
                        'cost_type' => 'ticket',
                        'amount' => (float) ($costData['amount'] ?? 0),
                        'currency' => strtoupper((string) ($costData['currency'] ?? 'PLN')),
                        'cost_date' => $departureDate->toDateString(),
                        'description' => 'Bilet - '.$employeeName,
                        'file_path' => $costData['attachment_path'] ?? null,
                        'notes' => collect([
                            ! empty($destinationStopLocationName) ? ('Przystanek docelowy: '.$destinationStopLocationName) : null,
                            $airportText,
                        ])->filter()->implode(' | ') ?: null,
                        'created_by' => auth()->id() ?? 1,
                    ]);
                }
            }

            // Create project assignments from date ranges (new multi-step form uses ranges)
            // If assignmentRanges exist, use only those (they contain date ranges)
            // Otherwise, fall back to day-based assignments for backward compatibility
            if (! empty($assignmentRanges)) {
                // Use assignment ranges (new way - one assignment per range)
                foreach ($assignmentRanges as $range) {
                    if (empty($range['employee_id']) || empty($range['project_id']) || empty($range['role_id']) || empty($range['start_date'])) {
                        continue;
                    }

                    $startDate = Carbon::parse($range['start_date']);
                    $endDate = ! empty($range['end_date']) ? Carbon::parse($range['end_date']) : $startDate;

                    // Create assignment with date range
                    $this->projectAssignmentService->createAssignment(
                        Project::find($range['project_id']),
                        Employee::find($range['employee_id']),
                        Role::find($range['role_id']),
                        $startDate,
                        $endDate,
                        null,
                        $departure->id
                    );
                }
            } else {
                // Fallback: Create project assignments from day-based assignments (days 1-7) - old way
                foreach ($validated['assignments'] as $assignment) {
                    $dayNumber = $assignment['day'];
                    $assignmentDate = $arrivalDate->copy()->addDays($dayNumber - 1);

                    // For day-based assignments, create single-day assignment
                    $this->projectAssignmentService->createAssignment(
                        Project::find($assignment['project_id']),
                        Employee::find($assignment['employee_id']),
                        Role::find($assignment['role_id']),
                        $assignmentDate,
                        $assignmentDate, // Single day assignment
                        null,
                        $departure->id
                    );
                }
            }

            // Create accommodation assignments
            $accommodationAssignments = $validated['accommodation_assignments'] ?? [];
            foreach ($accommodationAssignments as $employeeId => $assignment) {
                if (! is_array($assignment) || empty($assignment['accommodation_id'])) {
                    continue;
                }

                $employee = Employee::find($employeeId);
                $accommodation = Accommodation::find($assignment['accommodation_id']);

                if ($employee && $accommodation) {
                    $this->accommodationAssignmentService->createAssignment(
                        $employee,
                        $accommodation,
                        Carbon::parse($assignment['start_date']),
                        Carbon::parse($assignment['end_date']),
                        null,
                        $departure->id
                    );
                }
            }

            // Create vehicle assignments
            $vehicleAssignments = $validated['vehicle_assignments'] ?? [];
            foreach ($vehicleAssignments as $employeeId => $assignment) {
                if (! is_array($assignment) || empty($assignment['vehicle_id'])) {
                    continue;
                }

                $employee = Employee::find($employeeId);
                $vehicle = Vehicle::find($assignment['vehicle_id']);

                if ($employee && $vehicle) {
                    $this->vehicleAssignmentService->createAssignment(
                        $employee,
                        $vehicle,
                        VehiclePosition::from($assignment['position'] ?? 'passenger'),
                        Carbon::parse($assignment['start_date']),
                        Carbon::parse($assignment['end_date']),
                        null,
                        $departure->id
                    );
                }
            }

            // Create transfer event (public transport only, if configured in step 4)
            $transferConfig = $validated['transfer_config'] ?? [];
            if (! empty($validated['vehicle_id']) === false && ! empty($transferConfig)) {
                $transferVehicleId = ! empty($transferConfig['vehicle_id']) ? (int) $transferConfig['vehicle_id'] : null;
                $transferDriverEmployeeId = ! empty($transferConfig['driver_employee_id']) ? (int) $transferConfig['driver_employee_id'] : null;
                $transferBonusAmount = ! empty($transferConfig['bonus_amount']) ? (float) $transferConfig['bonus_amount'] : null;
                $transferBonusCurrency = ! empty($transferConfig['bonus_currency']) ? strtoupper($transferConfig['bonus_currency']) : 'PLN';
                $transferPickupLocationId = ! empty($transferConfig['pickup_location_id']) ? (int) $transferConfig['pickup_location_id'] : null;
                $transferRouteDistance = $transferConfig['route_distance'] ?? null;
                $transferRouteDuration = $transferConfig['route_duration'] ?? null;
                $normalizedTransferWaypoints = LogisticsEvent::normalizeRouteWaypointsFromPayload(
                    ! empty($transferConfig['route_waypoints']) && is_array($transferConfig['route_waypoints'])
                        ? $transferConfig['route_waypoints']
                        : null
                );
                $transferLocationStopNotes = LogisticsEvent::sanitizeLocationStopNotes(
                    isset($transferConfig['location_stop_notes']) && is_array($transferConfig['location_stop_notes'])
                        ? $transferConfig['location_stop_notes']
                        : null,
                    $normalizedTransferWaypoints
                );
                $endAirportLocationId = ! empty($transferConfig['end_airport_location_id']) ? (int) $transferConfig['end_airport_location_id'] : null;

                // from_location = pickup location (or end airport if no pickup), to_location = destination (last accommodation)
                $transferFromLocationId = $transferPickupLocationId ?? $endAirportLocationId ?? $destinationLocationId;

                $transferAttributes = [
                    'type' => \App\Enums\LogisticsEventType::TRANSFER,
                    'event_date' => $departureDate,
                    'end_date' => $departureDate,
                    'from_location_id' => $transferFromLocationId,
                    'to_location_id' => $destinationLocationId,
                    'vehicle_id' => $transferVehicleId,
                    'status' => LogisticsEventStatus::COMPLETED,
                    'notes' => 'Transfer z lotniska – automatycznie z wyjazdu #'.$departure->id,
                    'related_departure_id' => $departure->id,
                    'has_reassignment' => false,
                    'has_transport' => ! empty($transferVehicleId),
                    'created_by' => auth()->id() ?? 1,
                    'route_distance' => $transferRouteDistance,
                    'route_duration' => $transferRouteDuration ? (int) $transferRouteDuration : null,
                    'route_waypoints' => $normalizedTransferWaypoints !== [] ? $normalizedTransferWaypoints : null,
                    'destination_stop_location' => null,
                ];
                if (Schema::hasColumn('logistics_events', 'location_stop_notes')) {
                    $transferAttributes['location_stop_notes'] = $transferLocationStopNotes;
                }

                $transfer = LogisticsEvent::create($transferAttributes);

                // Link participants to transfer
                foreach ($employeeIds as $employeeId) {
                    $transfer->participants()->create(['employee_id' => $employeeId]);
                }

                // Create driver adjustment if bonus is set
                if ($transferDriverEmployeeId && $transferBonusAmount > 0) {
                    \App\Models\Adjustment::create([
                        'employee_id' => $transferDriverEmployeeId,
                        'payroll_id' => null,
                        'logistics_event_id' => $transfer->id,
                        'type' => 'bonus',
                        'amount' => $transferBonusAmount,
                        'currency' => $transferBonusCurrency,
                        'notes' => 'Uznanie za kierowanie transferem #'.$transfer->id,
                        'date' => $departureDate->toDateString(),
                    ]);
                }
            }

            DB::commit();

            // Clear session data after SUCCESS
            session()->forget('departure_v2_data');

            return redirect()
                ->route('departures.index')
                ->with('success', 'Wyjazd został utworzony pomyślnie!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            return redirect()
                ->route('departures.create-v2')
                ->withInput()
                ->withErrors($e->errors())
                ->with('error', 'Błąd walidacji danych. Sprawdź wprowadzone dane.');
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            Log::error('Database error creating V2 departure: '.$e->getMessage(), [
                'exception' => $e,
                'request' => $validated,
            ]);

            // Provide user-friendly error message for common database errors
            $errorMessage = 'Wystąpił błąd podczas zapisywania danych do bazy.';
            if (str_contains($e->getMessage(), 'Invalid JSON')) {
                $errorMessage = 'Błąd zapisu danych trasy. Sprawdź czy wszystkie przystanki mają poprawne współrzędne.';
            } elseif (str_contains($e->getMessage(), 'foreign key constraint')) {
                $errorMessage = 'Błąd zapisu: Niektóre z wybranych danych nie istnieją w systemie. Odśwież stronę i spróbuj ponownie.';
            } elseif (str_contains($e->getMessage(), 'Integrity constraint violation')) {
                $errorMessage = 'Błąd zapisu: Niektóre dane są nieprawidłowe lub już istnieją w systemie.';
            } elseif (str_contains($e->getMessage(), 'location_stop_notes')) {
                $errorMessage = 'Baza wymaga aktualizacji: uruchom na serwerze `php artisan migrate` (brakuje kolumny notatek przy przystankach).';
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', $errorMessage);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating V2 departure: '.$e->getMessage(), [
                'exception' => $e,
                'request' => $validated,
                'trace' => $e->getTraceAsString(),
            ]);

            // Provide user-friendly error message
            $errorMessage = 'Wystąpił nieoczekiwany błąd podczas tworzenia wyjazdu.';
            if (str_contains($e->getMessage(), 'OpenRouteService')) {
                $errorMessage = 'Błąd podczas planowania trasy. Sprawdź czy wszystkie przystanki mają poprawne współrzędne i spróbuj ponownie.';
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', $errorMessage);
        }
    }

    private function summarizeMoneyByCurrency(Collection $items): string
    {
        if ($items->isEmpty()) {
            return '';
        }

        return $items->groupBy('currency')
            ->map(fn (Collection $group, string $currency) => number_format((float) $group->sum('amount'), 2).' '.$currency)
            ->implode(' + ');
    }
}
