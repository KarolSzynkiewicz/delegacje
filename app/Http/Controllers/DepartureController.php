<?php

namespace App\Http\Controllers;

use App\Services\DepartureService;
use App\Services\AssignmentQueryService;
use App\Services\ProjectAssignmentService;
use App\Services\VehicleAssignmentService;
use App\Services\AccommodationAssignmentService;
use App\Models\Vehicle;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Role;
use App\Models\Accommodation;
use App\Enums\LogisticsEventType;
use App\Enums\LogisticsEventStatus;
use App\Http\Requests\StoreDepartureRequest;
use App\Http\Requests\UpdateDepartureRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DepartureController extends Controller
{
    public function __construct(
        protected DepartureService $departureService,
        protected AssignmentQueryService $assignmentQueryService,
        protected ProjectAssignmentService $projectAssignmentService,
        protected VehicleAssignmentService $vehicleAssignmentService,
        protected AccommodationAssignmentService $accommodationAssignmentService
    ) {}

    /**
     * Display a listing of departures.
     */
    public function index(): View
    {
        $departures = LogisticsEvent::where('type', LogisticsEventType::DEPARTURE)
            ->with(['vehicle', 'fromLocation', 'toLocation', 'creator', 'participants.employee'])
            ->orderBy('event_date', 'desc')
            ->paginate(20);

        return view('departures.index', compact('departures'));
    }

    /**
     * Show the form for creating a new departure.
     */
    public function create(Request $request): View
    {
        $locations = Location::where('id', '!=', Location::getBase()->id)
            ->orderBy('name')
            ->get();

        $vehicles = Vehicle::where('type', 'company_vehicle')
            ->orderBy('registration_number')
            ->get();

        $baseLocation = Location::getBase();

        // Sprawdź czy daty są w przeszłości (z linku)
        $isDateInPast = false;
        $departureDate = $request->query('departure_date');
        $endDate = $request->query('end_date');
        
        if ($departureDate) {
            $isDateInPast = Carbon::parse($departureDate)->startOfDay()->isPast();
        }
        if ($endDate && !$isDateInPast) {
            $isDateInPast = Carbon::parse($endDate)->startOfDay()->isPast();
        }

        return view('departures.create', compact('locations', 'vehicles', 'baseLocation', 'isDateInPast'));
    }

    /**
     * Store a newly created departure.
     */
    public function store(StoreDepartureRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $employeeIds = $validated['employee_ids'];
            $departureDate = Carbon::parse($validated['departure_date']);
            $endDate = Carbon::parse($validated['end_date']);
            $toLocationId = $validated['to_location_id'];
            $vehicleId = $validated['vehicle_id'] ?? null;
            $notes = $validated['notes'] ?? null;

            $event = $this->departureService->commitDeparture(
                $employeeIds,
                $departureDate,
                $endDate,
                $toLocationId,
                $vehicleId,
                $notes
            );

            return redirect()
                ->route('departures.show', $event)
                ->with('success', 'Wyjazd został utworzony pomyślnie.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Wystąpił błąd podczas tworzenia wyjazdu: ' . $e->getMessage())
                ->withInput();
        }
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
            'projectAssignments.project',
            'vehicleAssignments.vehicle',
            'accommodationAssignments.accommodation',
        ]);

        return view('departures.show', compact('departure'));
    }

    /**
     * Show the form for editing a departure.
     * 
     * DEPRECATED: Redirect to show page instead.
     */
    public function edit(LogisticsEvent $departure): View|RedirectResponse
    {
        // Only PLANNED and COMPLETED departures can be edited
        if (!in_array($departure->status, [LogisticsEventStatus::PLANNED, LogisticsEventStatus::COMPLETED])) {
            return back()->with('error', 'Nie można edytować anulowanego wyjazdu.');
        }

        $vehicles = Vehicle::orderBy('registration_number')->get();
        $locations = Location::where('is_base', false)->orderBy('name')->get();
        
        // Get current participants
        $currentEmployeeIds = $departure->participants()->pluck('employee_id')->toArray();
        
        // Sprawdź czy data wyjazdu jest w przeszłości
        $isDateInPast = $departure->event_date->startOfDay()->isPast();
        
        return view('departures.edit', compact('departure', 'vehicles', 'locations', 'currentEmployeeIds', 'isDateInPast'));
    }

    /**
     * Update a departure.
     */
    public function update(UpdateDepartureRequest $request, LogisticsEvent $departure): RedirectResponse
    {
        if ($departure->type !== LogisticsEventType::DEPARTURE) {
            abort(404);
        }

        // Only allow updating if status is not CANCELLED
        if ($departure->status === \App\Enums\LogisticsEventStatus::CANCELLED) {
            return redirect()
                ->route('departures.show', $departure)
                ->with('error', 'Nie można edytować anulowanych wyjazdów.');
        }

        $validated = $request->validated();

        try {
            // Reverse previous departure changes
            $this->departureService->reverseDeparture($departure);

            $employeeIds = $validated['employee_ids'];
            $departureDate = Carbon::parse($validated['departure_date']);
            $endDate = Carbon::parse($validated['end_date']);
            $toLocationId = $validated['to_location_id'];
            $vehicleId = $validated['vehicle_id'] ?? null;
            $notes = $validated['notes'] ?? null;
            $status = isset($validated['status']) 
                ? \App\Enums\LogisticsEventStatus::from($validated['status'])
                : null;

            $event = $this->departureService->commitDeparture(
                $employeeIds,
                $departureDate,
                $endDate,
                $toLocationId,
                $vehicleId,
                $notes,
                $departure,
                $status
            );

            return redirect()
                ->route('departures.show', $event)
                ->with('success', 'Wyjazd został zaktualizowany pomyślnie.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Wystąpił błąd podczas aktualizacji wyjazdu: ' . $e->getMessage())
                ->withInput();
        }
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
        if (!in_array($departure->status, [\App\Enums\LogisticsEventStatus::PLANNED, \App\Enums\LogisticsEventStatus::COMPLETED])) {
            return redirect()
                ->route('departures.show', $departure)
                ->with('error', 'Można anulować tylko wyjazdy ze statusem "Oczekuje na przypisanie" lub "Przypisany".');
        }

        // Find all affected assignments - SIMPLE! Use relationships
        $affectedProjectAssignments = $departure->projectAssignments()
            ->with(['employee', 'project', 'role'])
            ->where('is_cancelled', false)
            ->get();
        
        $affectedVehicleAssignments = $departure->vehicleAssignments()
            ->with(['employee', 'vehicle'])
            ->where('is_cancelled', false)
            ->get();
        
        // accommodation_assignments doesn't have is_cancelled column
        $affectedAccommodationAssignments = $departure->accommodationAssignments()
            ->with(['employee', 'accommodation'])
            ->get();

        return view('departures.prepare-cancellation', [
            'departure' => $departure,
            'affectedProjectAssignments' => $affectedProjectAssignments,
            'affectedVehicleAssignments' => $affectedVehicleAssignments,
            'affectedAccommodationAssignments' => $affectedAccommodationAssignments,
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
        if (!in_array($departure->status, [\App\Enums\LogisticsEventStatus::PLANNED, \App\Enums\LogisticsEventStatus::COMPLETED])) {
            return redirect()
                ->route('departures.show', $departure)
                ->with('error', 'Można anulować tylko wyjazdy ze statusem "Oczekuje na przypisanie" lub "Przypisany".');
        }

        // Validate accept_consequences checkbox
        $request->validate([
            'accept_consequences' => 'sometimes|accepted',
        ], [
            'accept_consequences.accepted' => 'Musisz zaakceptować konsekwencje anulacji wyjazdu.',
        ]);

        try {
            $cancelledCounts = DB::transaction(function () use ($departure) {
                // Get participants before cancellation
                $participants = $departure->participants()->with('employee')->get();
                
                // SIMPLE! Use direct relationships - we know exactly which assignments belong to this departure
                $projectAssignments = $departure->projectAssignments()->where('is_cancelled', false)->get();
                $vehicleAssignments = $departure->vehicleAssignments()->where('is_cancelled', false)->get();
                // accommodation_assignments doesn't have is_cancelled column
                $accommodationAssignments = $departure->accommodationAssignments()->get();
                
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
                    $locationTracker->syncOutsideBaseFlag($participant->employee, now());
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
                ];
            });

            $totalCancelled = $cancelledCounts['project'] + $cancelledCounts['vehicle'] + $cancelledCounts['accommodation'];
            
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
                if (!empty($details)) {
                    $message .= " (" . implode(', ', $details) . ")";
                }
                $message .= ".";
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
                ->with('error', 'Wystąpił błąd podczas anulowania wyjazdu: ' . $e->getMessage());
        }
    }

    /**
     * Prepare bulk assignment form (Step 2 of departure creation).
     */
    public function prepareBulkAssignment(Request $request): RedirectResponse|View
    {
        $rules = [
            'departure_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:departure_date',
            'to_location_id' => 'required|exists:locations,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
            'notes' => 'nullable|string',
        ];

        // If departure_date is in the past, require confirmation
        $departureDate = $request->input('departure_date');
        if ($departureDate && Carbon::parse($departureDate)->startOfDay()->isPast()) {
            $rules['confirm_past_date'] = 'accepted';
        }

        $validated = $request->validate($rules, [
            'departure_date.required' => 'Proszę podać datę wyjazdu.',
            'end_date.required' => 'Proszę podać datę przybycia.',
            'end_date.after_or_equal' => 'Data przybycia musi być taka sama lub późniejsza niż data wyjazdu.',
            'to_location_id.required' => 'Proszę wybrać lokalizację docelową.',
            'employee_ids.required' => 'Proszę wybrać co najmniej jednego uczestnika.',
            'employee_ids.min' => 'Proszę wybrać co najmniej jednego uczestnika.',
            'confirm_past_date.accepted' => 'Musisz potwierdzić, że chcesz dodać wyjazd z datą w przeszłości.',
        ]);

        // Get base location ID
        $fromLocationId = Location::getBase()->id;
        
        // Prepare departure data
        $departureData = [
            'event_date' => $validated['departure_date'],
            'end_date' => $validated['end_date'],
            'from_location_id' => $fromLocationId,
            'to_location_id' => $validated['to_location_id'],
            'vehicle_id' => $validated['vehicle_id'] ?? null,
            'participants' => $validated['employee_ids'],
            'notes' => $validated['notes'] ?? null,
        ];
        
        // Save departure data in session (don't store in DB yet)
        session(['pending_departure' => $departureData]);

        // Load employees with their roles
        $employees = Employee::with('roles')->findMany($validated['employee_ids']);

        // Load resources for assignments
        $projects = Project::active()->orderBy('name')->get();
        $roles = Role::orderBy('name')->get();
        $vehicles = Vehicle::orderBy('registration_number')->get();
        $accommodations = Accommodation::orderBy('name')->get();

        $toLocation = Location::findOrFail($validated['to_location_id']);
        $arrivalDate = Carbon::parse($validated['end_date']);
        $weekEnd = $arrivalDate->copy()->endOfWeek();

        return view('departures.bulk-assignment', compact(
            'employees',
            'projects',
            'roles',
            'vehicles',
            'accommodations',
            'toLocation',
            'arrivalDate',
            'weekEnd',
            'departureData'
        ));
    }

    /**
     * Store departure with all assignments in atomic transaction.
     */
    public function storeWithAssignments(Request $request): RedirectResponse
    {
        // Get departure data from session
        $departureData = session('pending_departure');
        
        if (!$departureData) {
            return redirect()
                ->route('departures.create')
                ->with('error', 'Brak danych wyjazdu. Rozpocznij proces od początku.');
        }

        // Validate assignments
        $validated = $request->validate([
            'assignments' => 'required|array',
            'assignments.*.project_id' => 'required|exists:projects,id',
            'assignments.*.role_id' => 'required|exists:roles,id',
            'assignments.*.project_start_date' => 'required|date',
            'assignments.*.project_end_date' => 'required|date|after_or_equal:assignments.*.project_start_date',
            'assignments.*.vehicle_id' => 'required|exists:vehicles,id',
            'assignments.*.position' => 'required|in:driver,passenger',
            'assignments.*.vehicle_start_date' => 'required|date',
            'assignments.*.vehicle_end_date' => 'required|date|after_or_equal:assignments.*.vehicle_start_date',
            'assignments.*.accommodation_id' => 'required|exists:accommodations,id',
            'assignments.*.accommodation_start_date' => 'required|date',
            'assignments.*.accommodation_end_date' => 'required|date|after_or_equal:assignments.*.accommodation_start_date',
        ]);

        DB::beginTransaction();
        try {
            // 1. Create departure (logistics event)
            $departure = LogisticsEvent::create([
                'type' => LogisticsEventType::DEPARTURE,
                'event_date' => Carbon::parse($departureData['event_date']),
                'end_date' => Carbon::parse($departureData['end_date']),
                'from_location_id' => $departureData['from_location_id'],
                'to_location_id' => $departureData['to_location_id'],
                'vehicle_id' => $departureData['vehicle_id'] ?? null,
                'status' => LogisticsEventStatus::PLANNED,
                'notes' => $departureData['notes'] ?? null,
                'has_transport' => !empty($departureData['vehicle_id']),
            ]);

            // 2. Add participants to departure
            foreach ($departureData['participants'] as $employeeId) {
                $departure->participants()->create([
                    'employee_id' => $employeeId,
                ]);
            }

            // 3. Create all assignments for each participant
            $assignments = $validated['assignments'];
            
            foreach ($assignments as $employeeId => $assignmentData) {
                $employee = Employee::findOrFail($employeeId);

                // Project assignment
                $project = Project::findOrFail($assignmentData['project_id']);
                $role = Role::findOrFail($assignmentData['role_id']);

                $this->projectAssignmentService->createAssignment(
                    $project,
                    $employee,
                    $role,
                    Carbon::parse($assignmentData['project_start_date']),
                    Carbon::parse($assignmentData['project_end_date']),
                    null, // notes
                    $departure->id // Link to departure!
                );

                // Vehicle assignment
                $vehicle = Vehicle::findOrFail($assignmentData['vehicle_id']);
                
                $this->vehicleAssignmentService->createAssignment(
                    $vehicle,
                    $employee,
                    $assignmentData['position'],
                    Carbon::parse($assignmentData['vehicle_start_date']),
                    Carbon::parse($assignmentData['vehicle_end_date'])
                );

                // Accommodation assignment
                $accommodation = Accommodation::findOrFail($assignmentData['accommodation_id']);
                
                $this->accommodationAssignmentService->createAssignment(
                    $accommodation,
                    $employee,
                    Carbon::parse($assignmentData['accommodation_start_date']),
                    Carbon::parse($assignmentData['accommodation_end_date'])
                );
            }

            DB::commit();
            session()->forget('pending_departure');

            return redirect()
                ->route('weekly-overview.index', [
                    'start_date' => Carbon::parse($departureData['end_date'])->startOfWeek()->format('Y-m-d')
                ])
                ->with('success', "Wyjazd oraz wszystkie przypisania zostały utworzone! ({$departure->participants->count()} pracowników)");

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback();
            
            return back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('error', 'Błąd walidacji: ' . $e->getMessage());
                
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Error creating departure with assignments', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Wystąpił błąd podczas zapisywania: ' . $e->getMessage());
        }
    }
}
