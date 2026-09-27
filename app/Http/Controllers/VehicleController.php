<?php

namespace App\Http\Controllers;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Enums\VehicleLifecycleEventType;
use App\Enums\VehiclePosition;
use App\Http\Controllers\Concerns\HandlesImageUpload;
use App\Http\Requests\CorrectVehiclePlacementRequest;
use App\Http\Requests\RetireVehicleRequest;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\AccommodationAssignment;
use App\Models\LogisticsEvent;
use App\Models\ProjectAssignment;
use App\Models\Vehicle;
use App\Services\LocationTrackingService;
use App\Services\VehicleAssignmentReleaseService;
use App\Services\VehicleLifecycleService;
use App\Services\VehiclePlacementCorrectionService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VehicleController extends Controller
{
    use HandlesImageUpload;

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        // Dane są pobierane przez komponent Livewire VehiclesTable
        return view('vehicles.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('vehicles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $validated = $this->processImageUpload($request->validated(), $request, 'vehicles');
        Vehicle::create($validated);

        return redirect()->route('vehicles.index')->with('success', 'Pojazd został dodany.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Vehicle $vehicle, \Illuminate\Http\Request $request): View
    {
        $filter = $request->get('filter', 'all'); // 'all' or 'active'
        $tab = $request->query('tab', 'info');
        if (! in_array($tab, ['info', 'repairs', 'assignments', 'consumptions', 'logistics', 'lifecycle'], true)) {
            $tab = 'info';
        }

        $assignmentsQuery = $vehicle->assignments()
            ->with(['employee'])
            ->orderBy('start_date', 'desc');

        if ($filter === 'active') {
            $assignmentsQuery->active();
        }

        $assignments = $assignmentsQuery->paginate(10)->withQueryString();

        $placement = app(LocationTrackingService::class)->getVehicleLocationStatus($vehicle, Carbon::now());
        $assignmentsToRelease = app(VehicleAssignmentReleaseService::class)->coveringTodayOrLater($vehicle);
        $activeAssignments = $vehicle->assignments()->active()->with('employee')->get();
        $driverAssignment = $activeAssignments->first(
            fn ($assignment) => $assignment->position === VehiclePosition::DRIVER
        );
        $passengers = $activeAssignments
            ->reject(fn ($assignment) => $driverAssignment && $assignment->is($driverAssignment))
            ->values();
        $seatCapacity = max(1, (int) $vehicle->capacity);
        $occupancyCount = $activeAssignments->count();
        $todayCrew = collect();
        if ($driverAssignment?->employee) {
            $todayCrew->push(['employee' => $driverAssignment->employee, 'role' => 'Kierowca (dziś)']);
        }
        foreach ($passengers as $passenger) {
            if ($passenger->employee) {
                $todayCrew->push(['employee' => $passenger->employee, 'role' => 'Pasażer']);
            }
        }
        $crewIds = $todayCrew->pluck('employee.id')->filter()->values();
        $crewProjects = $crewIds->isEmpty()
            ? collect()
            : ProjectAssignment::query()
                ->active()
                ->whereIn('employee_id', $crewIds)
                ->with('project')
                ->get()
                ->pluck('project')
                ->filter()
                ->unique('id')
                ->values();
        $crewHomes = $crewIds->isEmpty()
            ? collect()
            : AccommodationAssignment::query()
                ->active()
                ->whereIn('employee_id', $crewIds)
                ->with('accommodation')
                ->get()
                ->pluck('accommodation')
                ->filter()
                ->unique('id')
                ->values();

        $recentLogistics = LogisticsEvent::query()
            ->where('vehicle_id', $vehicle->id)
            ->whereIn('type', [
                LogisticsEventType::DEPARTURE,
                LogisticsEventType::RETURN,
                LogisticsEventType::TRANSFER,
            ])
            ->where('status', '!=', LogisticsEventStatus::CANCELLED)
            ->with(['fromLocation', 'toLocation'])
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        $vehicle->load([
            'lifecycleEvents.createdBy',
            'comments.user',
            'equipmentConsumptions' => fn ($movements) => $movements
                ->with(['equipment', 'variant', 'warehouse.location', 'creator'])
                ->latest('id')
                ->limit(50),
        ]);

        $corrections = LogisticsEvent::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('type', LogisticsEventType::PLACEMENT_CORRECTION)
            ->with('creator')
            ->orderByDesc('id')
            ->get();

        $vehicleJournal = collect();
        foreach ($vehicle->lifecycleEvents as $event) {
            $vehicleJournal->push([
                'at' => $event->occurred_at,
                'dot' => $event->type === VehicleLifecycleEventType::Retired ? '#f43f5e' : '#14b8a6',
                'title' => $event->type->label(),
                'meta' => trim(($event->createdBy?->name ?: '—').' · '.$event->occurred_at?->format('Y-m-d, H:i')),
                'note' => $event->note,
                'aside' => $event->reason?->label() ?? '',
            ]);
        }
        foreach ($corrections as $event) {
            $when = $event->created_at ?? $event->event_date;
            $vehicleJournal->push([
                'at' => $when,
                'dot' => '#fbbf24',
                'title' => 'Korekta położenia',
                'meta' => trim(($event->creator?->name ?: '—').' · '.$when?->format('Y-m-d, H:i')),
                'note' => $event->notes,
                'aside' => $event->sets_outside_base ? 'Poza bazą' : 'W bazie',
            ]);
        }
        $vehicleJournal = $vehicleJournal
            ->sortByDesc(fn (array $row) => $row['at']?->getTimestamp() ?? 0)
            ->values();

        return view('vehicles.show', compact(
            'vehicle',
            'assignments',
            'filter',
            'placement',
            'driverAssignment',
            'occupancyCount',
            'seatCapacity',
            'todayCrew',
            'crewProjects',
            'crewHomes',
            'recentLogistics',
            'vehicleJournal',
            'assignmentsToRelease',
            'tab',
        ));
    }

    public function correctPlacement(
        CorrectVehiclePlacementRequest $request,
        Vehicle $vehicle,
        VehiclePlacementCorrectionService $corrections
    ): RedirectResponse {
        $corrections->correct(
            $vehicle,
            $request->string('placement')->toString() === 'field',
            $request->string('notes')->toString(),
            $request->user(),
        );

        return redirect()
            ->route('vehicles.show', $vehicle)
            ->with('success', 'Położenie pojazdu zostało poprawione.');
    }

    public function retire(RetireVehicleRequest $request, Vehicle $vehicle, VehicleLifecycleService $lifecycle): RedirectResponse
    {
        $lifecycle->retire(
            $vehicle,
            $request->enum('reason', \App\Enums\VehicleRetirementReason::class),
            $request->input('note'),
            $request->user(),
        );

        return redirect()
            ->route('vehicles.show', $vehicle)
            ->with('success', 'Pojazd został wycofany z floty. Historia zostaje.');
    }

    public function reinstate(Vehicle $vehicle, VehicleLifecycleService $lifecycle): RedirectResponse
    {
        $lifecycle->reinstate($vehicle, request()->user());

        return redirect()
            ->route('vehicles.show', $vehicle)
            ->with('success', 'Pojazd wrócił do aktywnej floty.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Vehicle $vehicle): View
    {
        return view('vehicles.edit', compact('vehicle'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $validated = $this->processImageUpload($request->validated(), $request, 'vehicles', $vehicle->image_path);
        $vehicle->update($validated);

        return redirect()->route('vehicles.show', $vehicle)->with('success', 'Pojazd został zaktualizowany.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->delete();

        return redirect()->route('vehicles.index')->with('success', 'Pojazd został usunięty.');
    }
}
