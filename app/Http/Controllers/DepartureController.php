<?php

namespace App\Http\Controllers;

use App\Services\DepartureService;
use App\Services\AssignmentQueryService;
use App\Models\Vehicle;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Enums\LogisticsEventType;
use App\Http\Requests\StoreDepartureRequest;
use App\Http\Requests\UpdateDepartureRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
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
            $toLocationId = $validated['to_location_id'];
            $vehicleId = $validated['vehicle_id'] ?? null;
            $notes = $validated['notes'] ?? null;

            $event = $this->departureService->commitDeparture(
                $employeeIds,
                $departureDate,
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
        ]);

        return view('departures.show', compact('departure'));
    }

    /**
     * Show the form for editing a departure.
     */
    public function edit(LogisticsEvent $departure): View|RedirectResponse
    {
        if ($departure->type !== LogisticsEventType::DEPARTURE) {
            abort(404);
        }

        // Only allow editing if status is not CANCELLED
        if ($departure->status === \App\Enums\LogisticsEventStatus::CANCELLED) {
            return redirect()
                ->route('departures.show', $departure)
                ->with('error', 'Nie można edytować anulowanych wyjazdów.');
        }

        $locations = Location::where('id', '!=', Location::getBase()->id)
            ->orderBy('name')
            ->get();

        $vehicles = Vehicle::where('type', 'company_vehicle')
            ->orderBy('registration_number')
            ->get();

        $baseLocation = Location::getBase();

        // Get current participants
        $currentEmployeeIds = $departure->participants->pluck('employee_id')->toArray();

        return view('departures.edit', compact('departure', 'locations', 'vehicles', 'baseLocation', 'currentEmployeeIds'));
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
            $toLocationId = $validated['to_location_id'];
            $vehicleId = $validated['vehicle_id'] ?? null;
            $notes = $validated['notes'] ?? null;
            $status = isset($validated['status']) 
                ? \App\Enums\LogisticsEventStatus::from($validated['status'])
                : null;

            $event = $this->departureService->commitDeparture(
                $employeeIds,
                $departureDate,
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
     * Cancel a departure.
     */
    public function cancel(LogisticsEvent $departure): RedirectResponse
    {
        if ($departure->type !== LogisticsEventType::DEPARTURE) {
            abort(404);
        }

        // Only allow cancellation if status is PLANNED
        if ($departure->status !== \App\Enums\LogisticsEventStatus::PLANNED) {
            return redirect()
                ->route('departures.show', $departure)
                ->with('error', 'Można anulować tylko wyjazdy ze statusem "Zaplanowane".');
        }

        $departure->update([
            'status' => \App\Enums\LogisticsEventStatus::CANCELLED,
        ]);

        return redirect()
            ->route('departures.show', $departure)
            ->with('success', 'Wyjazd został anulowany.');
    }
}
