<?php

namespace App\Http\Controllers;

use App\Services\DepartureService;
use App\Services\AssignmentQueryService;
use App\Models\Vehicle;
use App\Models\Location;
use App\Models\LogisticsEvent;
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
        protected AssignmentQueryService $assignmentQueryService
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
    public function create(): View
    {
        $locations = Location::where('id', '!=', Location::getBase()->id)
            ->orderBy('name')
            ->get();

        $vehicles = Vehicle::where('type', 'company_vehicle')
            ->orderBy('registration_number')
            ->get();

        $baseLocation = Location::getBase();

        return view('departures.create', compact('locations', 'vehicles', 'baseLocation'));
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
        
        return view('departures.edit', compact('departure', 'vehicles', 'locations', 'currentEmployeeIds'));
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
                
                // Log the cancellation
                Log::info('Departure cancelled - related assignments deleted', [
                    'departure_id' => $departure->id,
                    'deleted_project_assignments' => $cancelledProjectCount,
                    'deleted_vehicle_assignments' => $cancelledVehicleCount,
                    'deleted_accommodation_assignments' => $cancelledAccommodationCount,
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
}
