<?php

namespace App\Http\Controllers;

use App\Services\DepartureService;
use App\Services\AssignmentQueryService;
use App\Services\ProjectAssignmentService;
use App\Services\VehicleAssignmentService;
use App\Services\AccommodationAssignmentService;
use App\Services\VehicleValidationService;
use App\Models\Vehicle;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Role;
use App\Models\Accommodation;
use App\Enums\LogisticsEventType;
use App\Enums\LogisticsEventStatus;
use App\Enums\VehiclePosition;
use App\Http\Requests\StoreDepartureRequest;
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
        protected AccommodationAssignmentService $accommodationAssignmentService,
        protected VehicleValidationService $vehicleValidationService
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

        return view('departures.create', compact('vehicles', 'baseLocation', 'isDateInPast'));
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
        try {
            $rules = [
                'departure_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:departure_date',
                'vehicle_id' => 'nullable|exists:vehicles,id',
                'employee_ids' => 'required|array|min:1',
                'employee_ids.*' => 'exists:employees,id',
                'notes' => 'nullable|string',
            ];

            // If departure_date is in the past, require confirmation
            // Dziś nie jest w przeszłości - sprawdzamy czy data jest wcześniejsza niż dzisiaj
            $departureDate = $request->input('departure_date');
            $today = Carbon::today();
            if ($departureDate && Carbon::parse($departureDate)->startOfDay()->lt($today)) {
                $rules['confirm_past_date'] = 'accepted';
            }

            $validated = $request->validate($rules, [
                'departure_date.required' => 'Proszę podać datę wyjazdu.',
                'end_date.required' => 'Proszę podać datę przybycia.',
                'end_date.after_or_equal' => 'Data przybycia musi być taka sama lub późniejsza niż data wyjazdu.',
                'employee_ids.required' => 'Proszę wybrać co najmniej jednego uczestnika.',
                'employee_ids.min' => 'Proszę wybrać co najmniej jednego uczestnika.',
                'confirm_past_date.accepted' => 'Musisz potwierdzić, że chcesz dodać wyjazd z datą w przeszłości.',
            ]);
            
            // Validate vehicle availability if vehicle is selected
            if (!empty($validated['vehicle_id'])) {
                $vehicle = Vehicle::findOrFail($validated['vehicle_id']);
                $departureDate = Carbon::parse($validated['departure_date']);
                $endDate = Carbon::parse($validated['end_date']);
                
                try {
                    $this->vehicleValidationService->validateForLogisticsEventOrFail(
                        $vehicle,
                        $departureDate,
                        $endDate
                    );
                } catch (\Illuminate\Validation\ValidationException $e) {
                    // Add vehicle validation errors to request
                    return redirect()
                        ->route('departures.create', [
                            'departure_date' => $validated['departure_date'],
                            'end_date' => $validated['end_date']
                        ])
                        ->withInput()
                        ->withErrors($e->errors());
                }
            }
            
            // Get base location ID
            $fromLocationId = Location::getBase()->id;
            
            // Prepare departure data (to_location_id will be set in step 2 based on assigned projects)
            $departureData = [
                'event_date' => $validated['departure_date'],
                'end_date' => $validated['end_date'],
                'from_location_id' => $fromLocationId,
                'to_location_id' => null, // Will be determined in step 2 from assigned projects
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

            $arrivalDate = Carbon::parse($validated['end_date']);
            $weekEnd = $arrivalDate->copy()->endOfWeek();

            return view('departures.bulk-assignment', compact(
                'employees',
                'projects',
                'roles',
                'vehicles',
                'accommodations',
                'arrivalDate',
                'weekEnd',
                'departureData'
            ));

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('error', 'Proszę poprawić błędy w formularzu.');
        } catch (\Exception $e) {
            Log::error('Error preparing bulk assignment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Wystąpił błąd podczas przygotowania formularza: ' . $e->getMessage());
        }
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

        // Automatically determine destination location from assigned projects
        $destinationLocationId = $this->determineDestinationLocationFromAssignments($validated['assignments']);
        
        if (!$destinationLocationId) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Nie można określić lokalizacji docelowej. Upewnij się, że wszystkie projekty mają przypisaną lokalizację i wszystkie są w tej samej lokalizacji.');
        }

        DB::beginTransaction();
        try {
            // 1. Create departure (logistics event)
            $departure = LogisticsEvent::create([
                'type' => LogisticsEventType::DEPARTURE,
                'event_date' => Carbon::parse($departureData['event_date']),
                'end_date' => Carbon::parse($departureData['end_date']),
                'from_location_id' => $departureData['from_location_id'],
                'to_location_id' => $destinationLocationId,
                'vehicle_id' => $departureData['vehicle_id'] ?? null,
                'status' => LogisticsEventStatus::COMPLETED, // Wyjazd wymusza przypisanie, więc zawsze COMPLETED
                'notes' => $departureData['notes'] ?? null,
                'has_transport' => !empty($departureData['vehicle_id']),
                'created_by' => auth()->id() ?? 1,
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
                    $employee,
                    $vehicle,
                    VehiclePosition::from($assignmentData['position']),
                    Carbon::parse($assignmentData['vehicle_start_date']),
                    Carbon::parse($assignmentData['vehicle_end_date']),
                    null, // notes
                    $departure->id // Link to departure
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
            
            $errorMessage = 'Błąd walidacji danych. Proszę sprawdzić wprowadzone informacje.';
            if ($e->getMessage()) {
                $errorMessage .= ' ' . $e->getMessage();
            }
            
            return back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('error', $errorMessage);
                
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Error creating departure with assignments', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Wystąpił błąd podczas zapisywania wyjazdu. Spróbuj ponownie lub skontaktuj się z administratorem.');
        }
    }

    /**
     * Determine destination location from assigned projects.
     * Returns the unique location ID if all projects have the same location, null otherwise.
     */
    protected function determineDestinationLocationFromAssignments(array $assignments): ?int
    {
        $locationIds = [];
        
        foreach ($assignments as $employeeId => $assignmentData) {
            if (empty($assignmentData['project_id'])) {
                continue;
            }
            
            $project = Project::with('location')->find($assignmentData['project_id']);
            if (!$project || !$project->location_id) {
                return null; // Project has no location
            }
            
            $locationIds[] = $project->location_id;
        }
        
        if (empty($locationIds)) {
            return null; // No projects assigned
        }
        
        // Get unique location IDs
        $uniqueLocationIds = array_unique($locationIds);
        
        // If all projects are in the same location, return that location
        if (count($uniqueLocationIds) === 1) {
            return reset($uniqueLocationIds);
        }
        
        // Multiple locations - this is an error case
        return null;
    }

    /**
     * Show the V2 form for creating a new departure with drag-and-drop.
     */
    public function createV2(Request $request): View
    {
        $vehicles = Vehicle::where('type', 'company_vehicle')
            ->orderBy('registration_number')
            ->get();

        $baseLocation = Location::getBase();

        return view('departures.create-v2', compact('vehicles', 'baseLocation'));
    }

    /**
     * Show step 2: Accommodation assignment for assigned employees.
     */
    public function createV2Step2(Request $request): View
    {
        $departureData = session('departure_v2');
        
        if (!$departureData || !isset($departureData['step1'])) {
            return redirect()
                ->route('departures.create-v2')
                ->with('error', 'Brak danych z kroku 1. Rozpocznij od początku.');
        }

        $accommodations = Accommodation::orderBy('name')->get();
        $baseLocation = Location::getBase();

        return view('departures.create-v2-step2', compact('accommodations', 'baseLocation'));
    }

    /**
     * Show step 3: Vehicle assignment for assigned employees.
     */
    public function createV2Step3(Request $request): View
    {
        $departureData = session('departure_v2');
        
        if (!$departureData || !isset($departureData['step1']) || !isset($departureData['step2'])) {
            return redirect()
                ->route('departures.create-v2-step2')
                ->with('error', 'Brak danych z kroku 2. Rozpocznij od początku.');
        }

        $vehicles = Vehicle::where('type', 'company_vehicle')
            ->orderBy('registration_number')
            ->get();
        $baseLocation = Location::getBase();

        return view('departures.create-v2-step3', compact('vehicles', 'baseLocation'));
    }

    /**
     * Save draft version of departure (stored in session).
     */
    public function saveDraftV2(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'departure_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:departure_date',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'assignments' => 'nullable|array',
            'vehicle_seats' => 'nullable|array',
        ]);

        // Store in session
        session(['departure_draft_v2' => $validated]);

        return response()->json([
            'success' => true,
            'message' => 'Wersja robocza zapisana',
        ]);
    }

    /**
     * Clear draft version of departure from session.
     */
    public function clearDraftV2(): RedirectResponse
    {
        session()->forget('departure_draft_v2');
        
        return redirect()
            ->route('departures.create-v2')
            ->with('success', 'Wersja robocza została wyczyszczona');
    }

    /**
     * Store the final V2 departure with all assignments.
     */
    public function storeV2(Request $request): RedirectResponse
    {
        // Get data from session (multi-step form)
        $departureData = session('departure_v2');
        
        if (!$departureData || !isset($departureData['step1'])) {
            return redirect()
                ->route('departures.create-v2')
                ->with('error', 'Brak danych z formularza. Rozpocznij od początku.');
        }
        
        $step1 = $departureData['step1'];
        $step2 = $departureData['step2'] ?? [];
        $step3 = $departureData['step3'] ?? [];
        
        // Transform assignments from nested structure to flat array
        $assignmentsData = $step1['assignments'] ?? [];
        
        // Handle case where assignments might be a JSON string
        if (is_string($assignmentsData)) {
            $assignmentsData = json_decode($assignmentsData, true) ?? [];
        }
        
        // Ensure it's an array
        if (!is_array($assignmentsData)) {
            $assignmentsData = [];
        }
        
        $flatAssignments = [];
        
        foreach ($assignmentsData as $dayKey => $projects) {
            // Extract day number from "day_1", "day_2", etc.
            $dayNumber = (int) str_replace('day_', '', $dayKey);
            
            if (!is_array($projects)) {
                continue;
            }
            
            foreach ($projects as $projectId => $roles) {
                if (!is_array($roles)) {
                    continue;
                }
                
                foreach ($roles as $roleId => $employeeIds) {
                    if (!is_array($employeeIds)) {
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
        
        // Get assignment ranges from step1 (for days beyond 7)
        $assignmentRanges = $step1['assignment_ranges'] ?? [];
        
        // Validate data
        $validated = [
            'departure_date' => $step1['departure_date'],
            'end_date' => $step1['end_date'],
            'vehicle_id' => $step1['vehicle_id'] ?? null,
            'assignments' => $flatAssignments,
            'assignment_ranges' => $assignmentRanges,
        ];
        
        // Basic validation
        if (empty($validated['departure_date']) || empty($validated['end_date'])) {
            return redirect()
                ->route('departures.create-v2')
                ->with('error', 'Brak wymaganych dat wyjazdu.');
        }
        
        // Validate assignment ranges separately
        if (!empty($assignmentRanges)) {
            foreach ($assignmentRanges as $key => $range) {
                if (empty($range['employee_id']) || empty($range['project_id']) || empty($range['role_id']) || empty($range['start_date']) || empty($range['end_date'])) {
                    continue; // Skip invalid ranges
                }
                
                $startDate = Carbon::parse($range['start_date']);
                $endDate = Carbon::parse($range['end_date']);
                
                if ($endDate->lt($startDate)) {
                    return redirect()
                        ->back()
                        ->withInput()
                        ->with('error', "Nieprawidłowy zakres dat dla przypisania: {$range['start_date']} - {$range['end_date']}");
                }
            }
        }

        DB::beginTransaction();
        try {
            $departureDate = Carbon::parse($validated['departure_date']);
            $endDate = Carbon::parse($validated['end_date']);
            $arrivalDate = $endDate;

            // Determine destination location from assigned projects (both assignments and ranges)
            $projectIds = collect($validated['assignments'])->pluck('project_id')->unique();
            foreach ($assignmentRanges as $range) {
                if (!empty($range['project_id'])) {
                    $projectIds->push($range['project_id']);
                }
            }
            $projectIds = $projectIds->unique();
            
            if ($projectIds->isEmpty()) {
                DB::rollBack();
                return redirect()
                    ->back()
                    ->with('error', 'Brak przypisań do projektów.');
            }
            
            $projects = Project::whereIn('id', $projectIds)->with('location')->get();
            
            $locationIds = $projects->pluck('location_id')->filter()->unique();
            if ($locationIds->count() !== 1) {
                DB::rollBack();
                return redirect()
                    ->back()
                    ->with('error', 'Wszystkie projekty muszą być w tej samej lokalizacji.');
            }
            
            $destinationLocationId = $locationIds->first();
            $baseLocation = Location::getBase();

            // Create departure
            $departure = LogisticsEvent::create([
                'type' => LogisticsEventType::DEPARTURE,
                'event_date' => $departureDate,
                'end_date' => $endDate,
                'from_location_id' => $baseLocation->id,
                'to_location_id' => $destinationLocationId,
                'vehicle_id' => $validated['vehicle_id'] ?? null,
                'status' => LogisticsEventStatus::COMPLETED,
                'notes' => null,
                'has_transport' => !empty($validated['vehicle_id']),
                'created_by' => auth()->id() ?? 1,
            ]);

            // Get unique employee IDs from both assignments and ranges
            $employeeIds = collect($validated['assignments'])->pluck('employee_id')->unique();
            foreach ($assignmentRanges as $range) {
                if (!empty($range['employee_id'])) {
                    $employeeIds->push($range['employee_id']);
                }
            }
            $employeeIds = $employeeIds->unique();

            // Add participants
            foreach ($employeeIds as $employeeId) {
                $departure->participants()->create([
                    'employee_id' => $employeeId,
                ]);
            }

            // Create project assignments from date ranges (new multi-step form uses ranges)
            // If assignmentRanges exist, use only those (they contain date ranges)
            // Otherwise, fall back to day-based assignments for backward compatibility
            if (!empty($assignmentRanges)) {
                // Use assignment ranges (new way - one assignment per range)
                foreach ($assignmentRanges as $range) {
                    if (empty($range['employee_id']) || empty($range['project_id']) || empty($range['role_id']) || empty($range['start_date'])) {
                        continue;
                    }
                    
                    $startDate = Carbon::parse($range['start_date']);
                    $endDate = !empty($range['end_date']) ? Carbon::parse($range['end_date']) : $startDate;
                    
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
            
            // Create accommodation assignments from step 2
            $accommodationAssignments = $step2['accommodation_assignments'] ?? [];
            foreach ($accommodationAssignments as $employeeId => $assignment) {
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

            // Create vehicle assignments from step 3
            $vehicleAssignments = $step3['vehicle_assignments'] ?? [];
            foreach ($vehicleAssignments as $employeeId => $assignment) {
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

            DB::commit();

            // Clear session data
            session()->forget('departure_v2');
            session()->forget('departure_draft_v2');

            return redirect()
                ->route('departures.index')
                ->with('success', 'Wyjazd został utworzony pomyślnie!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating V2 departure: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $validated,
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Wystąpił błąd podczas tworzenia wyjazdu: ' . $e->getMessage());
        }
    }

}
