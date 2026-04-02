<?php

namespace App\Livewire;

use App\Enums\Currency;
use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Models\Adjustment;
use App\Models\Employee;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Models\LogisticsEventParticipant;
use App\Models\Vehicle;
use App\Services\GeocodingService;
use App\Services\RoutePlanningService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class TransferPlanner extends Component
{
    use WithPagination;

    // Basic
    public string $transferDate = '';

    public ?int $vehicleId = null;

    public string $notes = '';

    // Route: ordered list of location IDs [from, ...waypoints, to]
    public array $waypointLocationIds = [];

    // Route result
    public ?array $routeData = null;

    public bool $isPlanningRoute = false;

    public ?string $routeError = null;

    // Participants
    public array $selectedEmployeeIds = [];

    // Driver payment
    public ?int $driverEmployeeId = null;

    public string $driverPaymentAmount = '';

    public string $driverPaymentCurrency = 'PLN';

    public ?int $driverPayrollId = null;

    // Search helpers
    public string $employeeSearch = '';

    public string $vehicleSearch = '';

    public string $locationSearch = '';

    // UI: which location slot to add next
    public ?int $addLocationId = null;

    protected RoutePlanningService $routePlanningService;

    protected GeocodingService $geocodingService;

    public function boot(RoutePlanningService $routePlanningService, GeocodingService $geocodingService): void
    {
        $this->routePlanningService = $routePlanningService;
        $this->geocodingService = $geocodingService;
    }

    public function mount(): void
    {
        $this->transferDate = now()->format('Y-m-d\TH:i');
        $this->driverPaymentCurrency = Currency::PLN->value;
    }

    // ─── Computed properties ────────────────────────────────────────────────────

    public function updatingEmployeeSearch(): void
    {
        $this->resetPage();
    }

    public function paginationView()
    {
        return 'vendor.livewire.simple-pagination';
    }

    public function getEmployeesQueryProperty()
    {
        $q = Employee::query()->orderBy('last_name')->orderBy('first_name');

        if ($this->employeeSearch !== '') {
            $search = mb_strtolower(trim($this->employeeSearch));
            $q->where(function ($inner) use ($search) {
                $inner->whereRaw('LOWER(CONCAT(first_name, \" \", last_name)) LIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(CONCAT(last_name, \" \", first_name)) LIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(first_name) LIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(last_name) LIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(phone) LIKE ?', ['%'.$search.'%']);
            });
        }

        return $q;
    }

    public function getEmployeesPageProperty()
    {
        return $this->employeesQuery->paginate(12);
    }

    public function getSelectedEmployeesProperty()
    {
        if (empty($this->selectedEmployeeIds)) {
            return collect();
        }

        return Employee::whereIn('id', $this->selectedEmployeeIds)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    public function getLocationsProperty()
    {
        return Location::orderBy('name')->get();
    }

    public function getFilteredLocationsForPickerProperty()
    {
        return $this->locations->filter(function (Location $loc) {
            if (! $this->locationSearch) {
                return true;
            }
            $q = mb_strtolower($this->locationSearch);

            return str_contains(mb_strtolower($loc->name), $q)
                || str_contains(mb_strtolower($loc->city ?? ''), $q);
        })->whereNotIn('id', $this->waypointLocationIds);
    }

    public function getWaypointLocationsProperty(): array
    {
        if (empty($this->waypointLocationIds)) {
            return [];
        }
        $locations = Location::whereIn('id', $this->waypointLocationIds)->get()->keyBy('id');
        $result = [];
        foreach ($this->waypointLocationIds as $id) {
            if ($loc = $locations->get($id)) {
                $result[] = $loc;
            }
        }

        return $result;
    }

    public function getVehiclesProperty()
    {
        return Vehicle::where('type', 'company_vehicle')
            ->orderBy('registration_number')
            ->get()
            ->filter(function (Vehicle $v) {
                if (! $this->vehicleSearch) {
                    return true;
                }
                $q = mb_strtolower($this->vehicleSearch);

                return str_contains(mb_strtolower($v->registration_number), $q)
                    || str_contains(mb_strtolower($v->brand ?? ''), $q)
                    || str_contains(mb_strtolower($v->model ?? ''), $q);
            });
    }

    public function getDriverPayrollsProperty()
    {
        if (! $this->driverEmployeeId) {
            return collect();
        }

        return \App\Models\Payroll::with('employee')
            ->where('employee_id', $this->driverEmployeeId)
            ->orderBy('period_start', 'desc')
            ->get();
    }

    public function getCurrenciesProperty(): array
    {
        return Currency::cases();
    }

    // ─── Waypoint management ────────────────────────────────────────────────────

    public function addWaypoint(): void
    {
        if (! $this->addLocationId) {
            return;
        }
        $id = (int) $this->addLocationId;
        if (! in_array($id, $this->waypointLocationIds)) {
            $this->waypointLocationIds[] = $id;
        }
        $this->addLocationId = null;
        $this->locationSearch = '';

        $baseEndpointMsg = $this->baseAsRouteEndpointMessage();
        if ($baseEndpointMsg !== null) {
            array_pop($this->waypointLocationIds);
            $this->waypointLocationIds = array_values($this->waypointLocationIds);
            $this->routeError = $baseEndpointMsg;
            $this->routeData = null;

            return;
        }

        $this->planRoute();
    }

    public function removeWaypoint(int $index): void
    {
        array_splice($this->waypointLocationIds, $index, 1);
        $this->waypointLocationIds = array_values($this->waypointLocationIds);
        $this->planRoute();
    }

    public function moveWaypointUp(int $index): void
    {
        if ($index <= 0) {
            return;
        }
        [$this->waypointLocationIds[$index - 1], $this->waypointLocationIds[$index]] =
            [$this->waypointLocationIds[$index], $this->waypointLocationIds[$index - 1]];

        $baseEndpointMsg = $this->baseAsRouteEndpointMessage();
        if ($baseEndpointMsg !== null) {
            [$this->waypointLocationIds[$index - 1], $this->waypointLocationIds[$index]] =
                [$this->waypointLocationIds[$index], $this->waypointLocationIds[$index - 1]];
            $this->routeError = $baseEndpointMsg;

            return;
        }

        $this->planRoute();
    }

    public function moveWaypointDown(int $index): void
    {
        if ($index >= count($this->waypointLocationIds) - 1) {
            return;
        }
        [$this->waypointLocationIds[$index], $this->waypointLocationIds[$index + 1]] =
            [$this->waypointLocationIds[$index + 1], $this->waypointLocationIds[$index]];

        $baseEndpointMsg = $this->baseAsRouteEndpointMessage();
        if ($baseEndpointMsg !== null) {
            [$this->waypointLocationIds[$index], $this->waypointLocationIds[$index + 1]] =
                [$this->waypointLocationIds[$index + 1], $this->waypointLocationIds[$index]];
            $this->routeError = $baseEndpointMsg;

            return;
        }

        $this->planRoute();
    }

    /**
     * Baza nie może być pierwszym ani ostatnim punktem trasy transferu (do tego służą zjazd/wyjazd).
     */
    private function baseAsRouteEndpointMessage(): ?string
    {
        if (count($this->waypointLocationIds) < 2) {
            return null;
        }

        $firstId = $this->waypointLocationIds[0];
        $lastId = $this->waypointLocationIds[array_key_last($this->waypointLocationIds)];
        $locs = Location::whereIn('id', [$firstId, $lastId])->get()->keyBy('id');
        $first = $locs->get($firstId);
        $last = $locs->get($lastId);

        $msg = 'Lokalizacja oznaczona jako baza nie może być pierwszym ani ostatnim punktem trasy. W tym celu ustaw zjazd lub wyjazd przy planowaniu delegacji.';

        if ($first && $first->is_base) {
            return $msg;
        }
        if ($last && $last->is_base) {
            return $msg;
        }

        return null;
    }

    // ─── Route planning ─────────────────────────────────────────────────────────

    public function planRoute(): void
    {
        if (count($this->waypointLocationIds) < 2) {
            $this->routeData = null;
            $this->routeError = null;

            return;
        }

        $baseMsg = $this->baseAsRouteEndpointMessage();
        if ($baseMsg !== null) {
            $this->routeError = $baseMsg;
            $this->routeData = null;

            return;
        }

        $locations = Location::whereIn('id', $this->waypointLocationIds)->get()->keyBy('id');

        // Geocode any locations missing coordinates
        foreach ($this->waypointLocationIds as $locId) {
            $loc = $locations->get($locId);
            if (! $loc) {
                continue;
            }
            if (! $loc->hasCoordinates()) {
                $address = $loc->getFullAddress();
                if ($address) {
                    try {
                        $coords = $this->geocodingService->geocode($address);
                        if ($coords && isset($coords['latitude'], $coords['longitude'])) {
                            $loc->update(['latitude' => $coords['latitude'], 'longitude' => $coords['longitude']]);
                            $loc->refresh();
                            $locations->put($loc->id, $loc);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Geocoding failed for location', ['id' => $loc->id]);
                    }
                }
            }
        }

        $missingCoords = [];
        foreach ($this->waypointLocationIds as $locId) {
            $loc = $locations->get($locId);
            if (! $loc || ! $loc->hasCoordinates()) {
                $missingCoords[] = $loc ? $loc->name : "ID:{$locId}";
            }
        }

        if (! empty($missingCoords)) {
            $this->routeError = 'Brak współrzędnych dla: '.implode(', ', $missingCoords).'. Edytuj lokalizację i uzupełnij adres.';
            $this->routeData = null;

            return;
        }

        $this->isPlanningRoute = true;
        $this->routeError = null;

        try {
            $ordered = array_map(fn ($id) => $locations->get($id), $this->waypointLocationIds);
            $start = array_shift($ordered);
            $end = array_pop($ordered);
            $intermediates = $ordered;

            $route = $this->routePlanningService->planRouteWithWaypoints($start, $end, $intermediates);

            if ($route) {
                $this->routeData = [
                    'distance' => $route['distance'],
                    'duration' => $route['duration'],
                ];
                $this->routeError = null;
            } else {
                $this->routeError = 'Nie udało się zaplanować trasy. Sprawdź współrzędne lokalizacji.';
                $this->routeData = null;
            }
        } catch (\Exception $e) {
            Log::error('Transfer route planning failed', ['message' => $e->getMessage()]);
            $this->routeError = 'Błąd podczas planowania trasy: '.$e->getMessage();
            $this->routeData = null;
        } finally {
            $this->isPlanningRoute = false;
        }
    }

    // ─── Participants ────────────────────────────────────────────────────────────

    public function toggleEmployee(int $employeeId): void
    {
        if (in_array($employeeId, $this->selectedEmployeeIds)) {
            $this->selectedEmployeeIds = array_values(
                array_filter($this->selectedEmployeeIds, fn ($id) => $id !== $employeeId)
            );
            if ($this->driverEmployeeId === $employeeId) {
                $this->driverEmployeeId = null;
                $this->driverPayrollId = null;
            }
        } else {
            $this->selectedEmployeeIds[] = $employeeId;
        }
    }

    public function updatedDriverEmployeeId(): void
    {
        $this->driverPayrollId = null;
    }

    // ─── Save ────────────────────────────────────────────────────────────────────

    public function save(): void
    {
        $this->validate([
            'transferDate' => ['required', 'date'],
            'waypointLocationIds' => ['required', 'array', 'min:2'],
            'vehicleId' => ['nullable', 'exists:vehicles,id'],
            'selectedEmployeeIds' => ['required', 'array', 'min:1'],
            'selectedEmployeeIds.*' => ['exists:employees,id'],
            'driverEmployeeId' => ['nullable', 'in:'.implode(',', $this->selectedEmployeeIds ?: [0])],
            'driverPaymentAmount' => ['nullable', 'numeric', 'min:0'],
            'driverPaymentCurrency' => ['required_with:driverPaymentAmount', 'in:'.implode(',', Currency::values())],
            'driverPayrollId' => [
                'nullable',
                'exists:payrolls,id',
                function ($attribute, $value, $fail) {
                    if ($value && $this->driverEmployeeId) {
                        $payroll = \App\Models\Payroll::find($value);
                        if ($payroll && $payroll->employee_id !== (int) $this->driverEmployeeId) {
                            $fail('Wybrany payroll nie należy do kierowcy.');
                        }
                    }
                },
            ],
        ], [
            'transferDate.required' => 'Data transferu jest wymagana.',
            'waypointLocationIds.required' => 'Dodaj co najmniej 2 lokalizacje (start i cel).',
            'waypointLocationIds.min' => 'Transfer musi mieć co najmniej lokalizację startową i docelową.',
            'selectedEmployeeIds.required' => 'Dodaj co najmniej jednego uczestnika.',
            'selectedEmployeeIds.min' => 'Dodaj co najmniej jednego uczestnika.',
            'driverPaymentCurrency.in' => 'Wybierz walutę z listy.',
        ]);

        $baseEndpointMsg = $this->baseAsRouteEndpointMessage();
        if ($baseEndpointMsg !== null) {
            $this->addError('waypointLocationIds', $baseEndpointMsg);

            return;
        }

        $fromLocationId = $this->waypointLocationIds[0];
        $toLocationId = $this->waypointLocationIds[count($this->waypointLocationIds) - 1];

        DB::transaction(function () use ($fromLocationId, $toLocationId) {
            $event = LogisticsEvent::create([
                'type' => LogisticsEventType::TRANSFER,
                'event_date' => $this->transferDate,
                'end_date' => $this->transferDate,
                'from_location_id' => $fromLocationId,
                'to_location_id' => $toLocationId,
                'vehicle_id' => $this->vehicleId ?: null,
                'has_transport' => empty($this->vehicleId),
                'status' => LogisticsEventStatus::PLANNED,
                'notes' => $this->notes ?: null,
                'route_distance' => $this->routeData['distance'] ?? null,
                'route_duration' => $this->routeData ? (int) $this->routeData['duration'] : null,
                'route_waypoints' => count($this->waypointLocationIds) > 2
                    ? array_slice($this->waypointLocationIds, 1, -1)
                    : null,
                'created_by' => Auth::id(),
            ]);

            foreach ($this->selectedEmployeeIds as $employeeId) {
                LogisticsEventParticipant::create([
                    'logistics_event_id' => $event->id,
                    'employee_id' => $employeeId,
                    'status' => 'pending',
                ]);
            }

            if ($this->driverEmployeeId && $this->driverPaymentAmount !== '' && (float) $this->driverPaymentAmount > 0) {
                Adjustment::create([
                    'employee_id' => $this->driverEmployeeId,
                    'logistics_event_id' => $event->id,
                    'payroll_id' => $this->driverPayrollId ?: null,
                    'amount' => $this->driverPaymentAmount,
                    'currency' => $this->driverPaymentCurrency,
                    'type' => 'bonus',
                    'date' => \Carbon\Carbon::parse($this->transferDate)->toDateString(),
                    'notes' => 'Wynagrodzenie za transfer',
                ]);
            }
        });

        session()->flash('success', 'Transfer został zapisany.');
        $this->redirect(route('transfers.index'));
    }

    public function render()
    {
        return view('livewire.transfer-planner');
    }
}
