<?php

namespace App\Http\Controllers;

use App\Services\ReturnTripService;
use App\Services\AssignmentQueryService;
use App\Models\Vehicle;
use App\Models\Location;
use App\Models\LogisticsEvent;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReturnTripController extends Controller
{
    public function __construct(
        protected ReturnTripService $returnTripService,
        protected AssignmentQueryService $assignmentQueryService
    ) {}

    /**
     * Display a listing of return trips.
     */
    public function index()
    {
        $this->authorize('viewAny', LogisticsEvent::class);

        $returnTrips = LogisticsEvent::where('type', 'return')
            ->with(['vehicle', 'fromLocation', 'toLocation', 'creator', 'participants.employee'])
            ->orderBy('event_date', 'desc')
            ->paginate(20);

        return view('return-trips.index', compact('returnTrips'));
    }

    /**
     * Show the form for creating a new return trip.
     */
    public function create()
    {
        $this->authorize('create', LogisticsEvent::class);

        // Get employees with active assignments
        $employees = $this->assignmentQueryService->getEmployeesWithActiveAssignments(Carbon::now());

        $vehicles = Vehicle::where('type', 'company_vehicle')
            ->orderBy('registration_number')
            ->get();

        $baseLocation = Location::getBase();

        return view('return-trips.create', compact('employees', 'vehicles', 'baseLocation'));
    }

    /**
     * Handle form submission - prepare return trip first.
     */
    public function prepareFromForm(Request $request)
    {
        $this->authorize('create', LogisticsEvent::class);

        $validated = $request->validate([
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
            'return_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Redirect to prepare route with data as query parameters
        return redirect()
            ->route('return-trips.prepare', $validated);
    }

    /**
     * Prepare return trip (dry-run / simulation).
     * Shows what will happen without committing changes.
     */
    public function prepare(Request $request)
    {
        $this->authorize('create', LogisticsEvent::class);

        // If no data provided, redirect to create form
        if (!$request->has('employee_ids') && !$request->old('employee_ids')) {
            return redirect()->route('return-trips.create')
                ->with('info', 'Proszę wypełnić formularz przygotowania zjazdu.');
        }

        // Get data from request (either from POST or from old input)
        $validated = $request->validate([
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
            'return_date' => 'required|date|after_or_equal:today',
            'edit_mode' => 'nullable|boolean',
            'return_trip_id' => 'nullable|exists:logistics_events,id',
        ]);

        try {
            $preparation = $this->returnTripService->prepareZjazd($validated);

            // Store preparation in session for commit
            session(['return_trip_preparation' => serialize($preparation)]);

            // Get employee names for display
            $employeeNames = [];
            foreach ($validated['employee_ids'] as $employeeId) {
                $employee = \App\Models\Employee::find($employeeId);
                if ($employee) {
                    $employeeNames[$employeeId] = $employee->full_name;
                }
            }

            $returnVehicle = isset($validated['vehicle_id']) 
                ? Vehicle::find($validated['vehicle_id']) 
                : null;

            $isEditMode = $validated['edit_mode'] ?? false;
            $returnTripId = $validated['return_trip_id'] ?? null;

            return view('return-trips.prepare', compact('preparation', 'employeeNames', 'returnVehicle', 'validated', 'isEditMode', 'returnTripId'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->route('return-trips.create')
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()
                ->route('return-trips.create')
                ->with('error', 'Wystąpił błąd podczas przygotowania zjazdu: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Store a newly created return trip (commit the preparation).
     */
    public function store(Request $request)
    {
        $this->authorize('create', LogisticsEvent::class);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
            'accept_consequences' => 'required|accepted',
            'return_trip_id' => 'nullable|exists:logistics_events,id',
        ]);

        // Get preparation from session
        $preparationSerialized = session('return_trip_preparation');
        if (!$preparationSerialized) {
            return redirect()
                ->route('return-trips.create')
                ->with('error', 'Sesja przygotowania zjazdu wygasła. Proszę przygotować zjazd ponownie.');
        }

        try {
            $preparation = unserialize($preparationSerialized);

            // Get existing event if editing
            $existingEvent = null;
            if (isset($validated['return_trip_id'])) {
                $existingEvent = LogisticsEvent::findOrFail($validated['return_trip_id']);
                $this->authorize('update', $existingEvent);
                
                // Only allow updating if status is PLANNED
                if ($existingEvent->status !== \App\Enums\LogisticsEventStatus::PLANNED) {
                    return redirect()
                        ->route('return-trips.show', $existingEvent)
                        ->with('error', 'Można edytować tylko zjazdy ze statusem "Zaplanowane".');
                }
            }

            // Commit the return trip (create or update)
            $event = $this->returnTripService->commitZjazd($preparation, $validated, $existingEvent);

            // Clear preparation from session
            session()->forget('return_trip_preparation');

            return redirect()
                ->route('return-trips.show', $event)
                ->with('success', 'Zjazd został utworzony pomyślnie.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Wystąpił błąd podczas tworzenia zjazdu: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified return trip.
     */
    public function show(LogisticsEvent $returnTrip)
    {
        $this->authorize('view', $returnTrip);

        $returnTrip->load([
            'vehicle',
            'fromLocation',
            'toLocation',
            'creator',
            'participants.employee',
            'participants.assignment' => function ($morphTo) {
                $morphTo->morphWith([
                    \App\Models\VehicleAssignment::class => ['vehicle'],
                    \App\Models\ProjectAssignment::class => ['project'],
                    \App\Models\AccommodationAssignment::class => ['accommodation'],
                ]);
            },
        ]);

        return view('return-trips.show', compact('returnTrip'));
    }

    /**
     * Show the form for editing a return trip.
     * Only allowed for PLANNED status.
     */
    public function edit(LogisticsEvent $returnTrip)
    {
        $this->authorize('update', $returnTrip);

        // Only allow editing if status is PLANNED
        if ($returnTrip->status !== \App\Enums\LogisticsEventStatus::PLANNED) {
            return redirect()
                ->route('return-trips.show', $returnTrip)
                ->with('error', 'Można edytować tylko zjazdy ze statusem "Zaplanowane".');
        }

        // Get employees with active assignments
        $employees = $this->assignmentQueryService->getEmployeesWithActiveAssignments(Carbon::now());

        $vehicles = Vehicle::where('type', 'company_vehicle')
            ->orderBy('registration_number')
            ->get();

        // Get current participants
        $currentEmployeeIds = $returnTrip->participants->pluck('employee_id')->toArray();

        return view('return-trips.edit', compact('returnTrip', 'employees', 'vehicles', 'currentEmployeeIds'));
    }

    /**
     * Update a return trip.
     * Reverses previous changes and applies new ones.
     */
    public function update(Request $request, LogisticsEvent $returnTrip)
    {
        $this->authorize('update', $returnTrip);

        // Only allow updating if status is PLANNED
        if ($returnTrip->status !== \App\Enums\LogisticsEventStatus::PLANNED) {
            return redirect()
                ->route('return-trips.show', $returnTrip)
                ->with('error', 'Można edytować tylko zjazdy ze statusem "Zaplanowane".');
        }

        $validated = $request->validate([
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
            'return_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string|max:1000',
            'accept_consequences' => 'required|accepted',
        ]);

        try {
            // Reverse previous return trip changes
            $this->returnTripService->reverseZjazd($returnTrip);

            // Prepare new return trip
            $preparation = $this->returnTripService->prepareZjazd($validated);

            // Commit new return trip (updates existing event)
            $event = $this->returnTripService->commitZjazd($preparation, $validated, $returnTrip);

            return redirect()
                ->route('return-trips.show', $event)
                ->with('success', 'Zjazd został zaktualizowany pomyślnie.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Wystąpił błąd podczas aktualizacji zjazdu: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Cancel a return trip.
     * Sets status to CANCELLED - does not reverse assignments.
     */
    public function cancel(LogisticsEvent $returnTrip)
    {
        $this->authorize('update', $returnTrip);

        // Only allow cancellation if status is PLANNED
        if ($returnTrip->status !== \App\Enums\LogisticsEventStatus::PLANNED) {
            return redirect()
                ->route('return-trips.show', $returnTrip)
                ->with('error', 'Można anulować tylko zjazdy ze statusem "Planowany".');
        }

        $returnTrip->update([
            'status' => \App\Enums\LogisticsEventStatus::CANCELLED,
        ]);

        return redirect()
            ->route('return-trips.show', $returnTrip)
            ->with('success', 'Zjazd został anulowany.');
    }
}
