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
     * Store a newly created return trip.
     */
    public function store(Request $request)
    {
        $this->authorize('create', LogisticsEvent::class);

        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
            'return_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $event = $this->returnTripService->createReturn($validated);

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
    public function show($returnTrip)
    {
        $returnTrip = LogisticsEvent::findOrFail($returnTrip);
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
}
