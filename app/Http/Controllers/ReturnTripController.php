<?php

namespace App\Http\Controllers;

use App\Services\ReturnTripService;
use App\Services\AssignmentQueryService;
use App\Models\Vehicle;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Enums\LogisticsEventType;
use App\Http\Requests\PrepareReturnTripRequest;
use App\Http\Requests\StoreReturnTripRequest;
use App\Http\Requests\UpdateReturnTripRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
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
    public function index(): View
    {
        $returnTrips = LogisticsEvent::where('type', LogisticsEventType::RETURN)
            ->with(['vehicle', 'fromLocation', 'toLocation', 'creator', 'participants.employee'])
            ->orderBy('event_date', 'desc')
            ->paginate(20);

        return view('return-trips.index', compact('returnTrips'));
    }

    /**
     * Show the form for creating a new return trip.
     */
    public function create(): View
    {
        // Employees will be loaded dynamically via Livewire based on selected date
        $vehicles = Vehicle::where('type', 'company_vehicle')
            ->orderBy('registration_number')
            ->get();

        $baseLocation = Location::getBase();

        return view('return-trips.create', compact('vehicles', 'baseLocation'));
    }

    /**
     * Handle form submission - prepare return trip first.
     * Works for both create and edit modes.
     */
    public function prepareFromForm(PrepareReturnTripRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Ręczne zbudowanie query string, tablice są poprawnie zakodowane
        $query = http_build_query($validated);

        return redirect(route('return-trips.prepare') . '?' . $query);
    }

    /**
     * Prepare return trip (dry-run / simulation).
     * Shows what will happen without committing changes.
     */
    public function prepare(PrepareReturnTripRequest $request): View|RedirectResponse
    {
        // If no data provided, redirect to appropriate form
        if (!$request->has('employee_ids') && !$request->old('employee_ids')) {
            $isEditMode = $request->input('edit_mode', false);
            $returnTripId = $request->input('return_trip_id');
            
            if ($isEditMode && $returnTripId) {
                return redirect()
                    ->route('return-trips.edit', $returnTripId)
                    ->with('info', 'Proszę wypełnić formularz przygotowania zjazdu.');
            }
            
            return redirect()->route('return-trips.create')
                ->with('info', 'Proszę wypełnić formularz przygotowania zjazdu.');
        }

        // Get data from request (either from POST or from old input)
        $validated = $request->validated();

        try {
            $employeeIds = $validated['employee_ids'];
            $returnDate = \Carbon\Carbon::parse($validated['return_date']);
            $returnVehicle = isset($validated['vehicle_id']) 
                ? Vehicle::findOrFail($validated['vehicle_id'])
                : null;
            
            $preparation = $this->returnTripService->prepareZjazd($employeeIds, $returnDate, $returnVehicle);

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
            $isEditMode = $request->input('edit_mode', false);
            $returnTripId = $request->input('return_trip_id');
            
            if ($isEditMode && $returnTripId) {
                return redirect()
                    ->route('return-trips.edit', $returnTripId)
                    ->withErrors($e->errors())
                    ->withInput();
            }
            
            return redirect()
                ->route('return-trips.create')
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            $isEditMode = $request->input('edit_mode', false);
            $returnTripId = $request->input('return_trip_id');
            
            if ($isEditMode && $returnTripId) {
                return redirect()
                    ->route('return-trips.edit', $returnTripId)
                    ->with('error', 'Wystąpił błąd podczas przygotowania zjazdu: ' . $e->getMessage())
                    ->withInput();
            }
            
            return redirect()
                ->route('return-trips.create')
                ->with('error', 'Wystąpił błąd podczas przygotowania zjazdu: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Store a newly created return trip (commit the preparation).
     */
    public function store(StoreReturnTripRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Get preparation from session
        $preparationSerialized = session('return_trip_preparation');
        if (!$preparationSerialized) {
            return redirect()
                ->route('return-trips.create')
                ->with('error', 'Sesja przygotowania zjazdu wygasła. Proszę przygotować zjazd ponownie.');
        }

        try {
            // Get existing event if editing
            $existingEvent = null;
            $preparation = null;
            
            if (isset($validated['return_trip_id'])) {
                $existingEvent = LogisticsEvent::findOrFail($validated['return_trip_id']);
                
                // Only allow updating if status is not CANCELLED
                if ($existingEvent->status === \App\Enums\LogisticsEventStatus::CANCELLED) {
                    return redirect()
                        ->route('return-trips.show', $existingEvent)
                        ->with('error', 'Nie można edytować anulowanych zjazdów.');
                }
                
                // Get preparation data from session before reversing
                // We need employee_ids, return_date, and vehicle_id from the original preparation
                $originalPreparation = unserialize($preparationSerialized);
                
                // Reverse previous return trip changes (restore original end dates)
                // This must be done BEFORE preparing new zjazd to restore the state
                $this->returnTripService->reverseZjazd($existingEvent);
                
                // Re-prepare the return trip with restored assignments
                // This is necessary because the original preparation was done on already-shortened assignments
                $employeeIds = $originalPreparation->employeeIds;
                $returnDate = $originalPreparation->returnDate;
                $returnVehicle = $originalPreparation->returnVehicle;
                
                $preparation = $this->returnTripService->prepareZjazd($employeeIds, $returnDate, $returnVehicle);
            } else {
                // For new return trips, use preparation from session
                $preparation = unserialize($preparationSerialized);
            }

            // Commit the return trip (create or update)
            $notes = $validated['notes'] ?? null;
            $status = isset($validated['status']) 
                ? \App\Enums\LogisticsEventStatus::from($validated['status'])
                : null;
            $event = $this->returnTripService->commitZjazd($preparation, $notes, $existingEvent, $status);

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
    public function show(LogisticsEvent $returnTrip): View
    {
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
    public function edit(LogisticsEvent $returnTrip): View|RedirectResponse
    {
        // Only allow editing if status is not CANCELLED and not COMPLETED
        if ($returnTrip->status === \App\Enums\LogisticsEventStatus::CANCELLED) {
            return redirect()
                ->route('return-trips.show', $returnTrip)
                ->with('error', 'Nie można edytować anulowanych zjazdów.');
        }
        
        if ($returnTrip->status === \App\Enums\LogisticsEventStatus::COMPLETED) {
            return redirect()
                ->route('return-trips.show', $returnTrip)
                ->with('error', 'Nie można edytować zakończonych zjazdów.');
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
    public function update(UpdateReturnTripRequest $request, LogisticsEvent $returnTrip): RedirectResponse
    {
        // Only allow updating if status is PLANNED
        if ($returnTrip->status !== \App\Enums\LogisticsEventStatus::PLANNED) {
            return redirect()
                ->route('return-trips.show', $returnTrip)
                ->with('error', 'Można edytować tylko zjazdy ze statusem "Zaplanowane".');
        }

        $validated = $request->validated();

        try {
            // Reverse previous return trip changes
            $this->returnTripService->reverseZjazd($returnTrip);

            // Prepare new return trip
            $employeeIds = $validated['employee_ids'];
            $returnDate = \Carbon\Carbon::parse($validated['return_date']);
            $returnVehicle = isset($validated['vehicle_id']) 
                ? Vehicle::findOrFail($validated['vehicle_id'])
                : null;
            
            $preparation = $this->returnTripService->prepareZjazd($employeeIds, $returnDate, $returnVehicle);

            // Commit new return trip (updates existing event)
            $notes = $validated['notes'] ?? null;
            $event = $this->returnTripService->commitZjazd($preparation, $notes, $returnTrip);

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
     * Reverses all assignments and sets status to CANCELLED.
     */
    public function cancel(LogisticsEvent $returnTrip): RedirectResponse
    {
        // Only allow cancellation if status is PLANNED
        if ($returnTrip->status !== \App\Enums\LogisticsEventStatus::PLANNED) {
            return redirect()
                ->route('return-trips.show', $returnTrip)
                ->with('error', 'Można anulować tylko zjazdy ze statusem "Planowany".');
        }

        try {
            // Reverse all assignments (restore original end dates, delete return trip assignments)
            $this->returnTripService->reverseZjazd($returnTrip);
            
            // Set status to CANCELLED
            $returnTrip->update([
                'status' => \App\Enums\LogisticsEventStatus::CANCELLED,
            ]);

            return redirect()
                ->route('return-trips.show', $returnTrip)
                ->with('success', 'Zjazd został anulowany i wszystkie przypisania zostały cofnięte.');
        } catch (\Exception $e) {
            return redirect()
                ->route('return-trips.show', $returnTrip)
                ->with('error', 'Wystąpił błąd podczas anulowania zjazdu: ' . $e->getMessage());
        }
    }
}
