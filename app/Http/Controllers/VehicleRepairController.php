<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleRepair;
use App\Models\Location;
use App\Enums\ServiceActionType;
use App\Enums\VehicleCondition;
use App\Http\Requests\StoreVehicleRepairRequest;
use App\Http\Requests\UpdateVehicleRepairRequest;
use App\Http\Requests\CompleteVehicleRepairRequest;
use App\Http\Requests\ConfirmVehicleRepairRequest;
use App\Services\VehicleRepairService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleRepairController extends Controller
{
    public function __construct(
        private readonly VehicleRepairService $repairService
    ) {}

    public function index(Request $request): View
    {
        $query = VehicleRepair::with(['vehicle', 'location'])
            ->orderBy('start_date', 'desc');

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->filled('status')) {
            match ($request->status) {
                'open'      => $query->open(),
                'completed' => $query->completed(),
                default     => null,
            };
        }

        if ($request->filled('action_type')) {
            $query->where('action_type', $request->action_type);
        }

        $repairs  = $query->paginate(20)->withQueryString();
        $vehicles = Vehicle::orderBy('registration_number')->get();

        return view('vehicle-repairs.index', compact('repairs', 'vehicles'));
    }

    public function create(Request $request): View
    {
        $vehicles   = Vehicle::orderBy('registration_number')->get();
        $locations  = Location::orderBy('name')->get();
        $actionTypes = ServiceActionType::cases();
        $selectedVehicleId = $request->vehicle_id;

        return view('vehicle-repairs.create', compact('vehicles', 'locations', 'actionTypes', 'selectedVehicleId'));
    }

    /**
     * Zapisz szkic formularza w sesji i przejdź do ekranu podsumowania (jak zjazd → prepare).
     */
    public function prepareFromForm(StoreVehicleRepairRequest $request): RedirectResponse
    {
        session(['vehicle_repair_draft' => $request->validated()]);

        return redirect()->route('vehicle-repairs.prepare');
    }

    /**
     * Podgląd skróceń przypisań i akceptacja konsekwencji.
     */
    public function prepare(): View|RedirectResponse
    {
        $draft = session('vehicle_repair_draft');
        if (!is_array($draft) || empty($draft['vehicle_id'])) {
            return redirect()
                ->route('vehicle-repairs.create')
                ->with('info', 'Wypełnij formularz rejestracji serwisu.');
        }

        $vehicle = Vehicle::findOrFail($draft['vehicle_id']);
        $repairStart = Carbon::parse($draft['start_date']);
        $assignmentPreview = $this->repairService->previewAssignmentChanges($vehicle, $repairStart);
        $workshopSummary = $this->workshopSummaryFromDraft($draft);
        $actionType = ServiceActionType::from($draft['action_type']);

        return view('vehicle-repairs.prepare', compact(
            'vehicle',
            'draft',
            'assignmentPreview',
            'workshopSummary',
            'actionType',
            'repairStart'
        ));
    }

    public function store(ConfirmVehicleRepairRequest $request): RedirectResponse
    {
        $draft = session('vehicle_repair_draft');
        if (!is_array($draft) || empty($draft['vehicle_id'])) {
            return redirect()
                ->route('vehicle-repairs.create')
                ->with('error', 'Sesja przygotowania serwisu wygasła. Wypełnij formularz ponownie.');
        }

        $vehicle = Vehicle::findOrFail($draft['vehicle_id']);
        $repair  = $this->repairService->startRepair($vehicle, $draft);
        session()->forget('vehicle_repair_draft');

        return redirect()
            ->route('vehicle-repairs.show', $repair)
            ->with('success', 'Naprawa została zarejestrowana. Aktywne przypisania pojazdu zostały skrócone.');
    }

    /**
     * @param  array<string, mixed>  $draft
     */
    private function workshopSummaryFromDraft(array $draft): string
    {
        if (!empty($draft['location_id'])) {
            $loc = Location::find($draft['location_id']);
            if ($loc) {
                return $loc->name . ($loc->city ? ', ' . $loc->city : '');
            }
        }

        if (!empty($draft['workshop_name'])) {
            return implode(', ', array_filter([
                $draft['workshop_name'],
                $draft['workshop_address'] ?? null,
                $draft['workshop_city'] ?? null,
            ]));
        }

        return '—';
    }

    public function show(VehicleRepair $vehicleRepair): View
    {
        $vehicleRepair->load(['vehicle', 'location', 'fixedCostEntry']);
        $conditionOptions = VehicleCondition::cases();

        return view('vehicle-repairs.show', compact('vehicleRepair', 'conditionOptions'));
    }

    public function edit(VehicleRepair $vehicleRepair): View
    {
        $vehicleRepair->load(['vehicle', 'location']);
        $locations   = Location::orderBy('name')->get();
        $actionTypes = ServiceActionType::cases();

        return view('vehicle-repairs.edit', compact('vehicleRepair', 'locations', 'actionTypes'));
    }

    public function update(UpdateVehicleRepairRequest $request, VehicleRepair $vehicleRepair): RedirectResponse
    {
        $this->repairService->updateRepair($vehicleRepair, $request->validated());

        return redirect()
            ->route('vehicle-repairs.show', $vehicleRepair)
            ->with('success', 'Naprawa została zaktualizowana.');
    }

    public function destroy(VehicleRepair $vehicleRepair): RedirectResponse
    {
        $this->repairService->deleteRepair($vehicleRepair);

        return redirect()
            ->route('vehicle-repairs.index')
            ->with('success', 'Naprawa została usunięta.');
    }

    public function completeForm(VehicleRepair $vehicleRepair): View|RedirectResponse
    {
        if ($vehicleRepair->isCompleted()) {
            return redirect()->route('vehicle-repairs.show', $vehicleRepair)
                ->with('info', 'Ta naprawa jest już zakończona.');
        }

        $vehicleRepair->load('vehicle');
        $conditionOptions = VehicleCondition::cases();

        return view('vehicle-repairs.complete', compact('vehicleRepair', 'conditionOptions'));
    }

    public function complete(CompleteVehicleRepairRequest $request, VehicleRepair $vehicleRepair): RedirectResponse
    {
        if ($vehicleRepair->isCompleted()) {
            return redirect()->route('vehicle-repairs.show', $vehicleRepair)
                ->with('info', 'Ta naprawa jest już zakończona.');
        }

        $this->repairService->completeRepair($vehicleRepair, $request->validated());

        return redirect()
            ->route('vehicle-repairs.show', $vehicleRepair)
            ->with('success', 'Naprawa zakończona. Koszt serwisu został automatycznie zaksięgowany.');
    }
}
