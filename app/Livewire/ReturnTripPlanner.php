<?php

namespace App\Livewire;

use App\Enums\Currency;
use App\Enums\EmployeeLocationState;
use App\Enums\LocationPurposeType;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Vehicle;
use App\Services\LocationTrackingService;
use App\Services\ReturnTripService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class ReturnTripPlanner extends Component
{
    use WithFileUploads;

    public string $returnDate = '';

    public string $endDate = '';

    public $vehicleId = '';

    public array $selectedEmployeeIds = [];

    public string $employeeSearch = '';

    public array $ticketCostsByEmployee = []; // [employee_id => ['amount' => ..., 'currency' => 'PLN', 'file' => ...]]

    public $sharedStartAirportLocationId = null;

    public $sharedEndAirportLocationId = null;

    public string $notes = '';

    public bool $showPreview = false;

    public array $previewData = [];

    public string $errorMessage = '';

    public function mount(): void
    {
        $this->returnDate = now()->toDateString();
        $this->endDate = now()->toDateString();
    }

    // ─── Computed properties ───────────────────────────────────────────────────

    public function getEmployeesListProperty(): array
    {
        if (empty($this->returnDate)) {
            return [];
        }

        $locationTrackingService = app(LocationTrackingService::class);
        $date = Carbon::parse($this->returnDate);
        $search = mb_strtolower(trim($this->employeeSearch));

        return Employee::with(['roles', 'assignments.project', 'accommodationAssignments.accommodation'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->filter(function (Employee $employee) use ($locationTrackingService, $date) {
                $status = $locationTrackingService->getLocationStatus($employee, $date);

                return $status['state'] === EmployeeLocationState::OUTSIDE_BASE;
            })
            ->filter(function (Employee $employee) use ($search) {
                if ($search === '') {
                    return true;
                }

                return str_contains(mb_strtolower($employee->full_name), $search);
            })
            ->values()
            ->map(fn (Employee $e) => [
                'id' => $e->id,
                'full_name' => $e->full_name,
                'first_name' => $e->first_name,
                'last_name' => $e->last_name,
                'image_url' => $e->image_url,
                'roles' => $e->roles->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])->toArray(),
            ])
            ->toArray();
    }

    public function getAvailableVehiclesProperty()
    {
        if (empty($this->returnDate)) {
            return collect();
        }

        $returnDate = Carbon::parse($this->returnDate);
        $locationTrackingService = app(LocationTrackingService::class);

        return Vehicle::where('type', 'company_vehicle')
            ->orderBy('registration_number')
            ->get()
            ->filter(function (Vehicle $vehicle) use ($returnDate, $locationTrackingService) {
                $status = $locationTrackingService->getVehicleLocationStatus($vehicle, $returnDate);

                return $status['outside_base'];
            });
    }

    public function getAvailableAirportsProperty()
    {
        return Location::whereHas('purposes', function ($q) {
                $q->where('purpose', LocationPurposeType::AIRPORT);
            })
            ->orderBy('name')
            ->get();
    }

    public function getSelectedEmployeesProperty(): \Illuminate\Support\Collection
    {
        if (empty($this->selectedEmployeeIds)) {
            return collect();
        }

        return Employee::with('roles')
            ->whereIn('id', $this->selectedEmployeeIds)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    public function getIsPublicTransportProperty(): bool
    {
        return empty($this->vehicleId);
    }

    public function getCurrencyCasesProperty(): array
    {
        return Currency::cases();
    }

    // ─── Actions ───────────────────────────────────────────────────────────────

    public function toggleEmployee(int $employeeId): void
    {
        if (in_array($employeeId, $this->selectedEmployeeIds)) {
            $this->selectedEmployeeIds = array_values(array_filter(
                $this->selectedEmployeeIds,
                fn ($id) => $id !== $employeeId
            ));
        } else {
            $this->selectedEmployeeIds[] = $employeeId;
        }
        $this->showPreview = false;
        $this->previewData = [];
    }

    public function updatedReturnDate(): void
    {
        $this->showPreview = false;
        $this->previewData = [];
        // Reset endDate if before returnDate
        if ($this->endDate && $this->endDate < $this->returnDate) {
            $this->endDate = $this->returnDate;
        }
    }

    public function updatedVehicleId(): void
    {
        $this->showPreview = false;
        $this->previewData = [];
    }

    public function prepareReturn(): void
    {
        $this->errorMessage = '';

        if (empty($this->returnDate)) {
            $this->addError('returnDate', 'Wybierz datę zjazdu.');

            return;
        }

        if (empty($this->selectedEmployeeIds)) {
            $this->addError('selectedEmployeeIds', 'Wybierz co najmniej jednego pracownika.');

            return;
        }

        try {
            $service = app(ReturnTripService::class);
            $vehicle = $this->vehicleId ? Vehicle::find($this->vehicleId) : null;

            $preparation = $service->prepareZjazd(
                $this->selectedEmployeeIds,
                Carbon::parse($this->returnDate),
                $vehicle,
                $this->endDate ? Carbon::parse($this->endDate) : null,
            );

            $this->previewData = [
                'is_valid' => $preparation->isValid,
                'conflicts' => $preparation->conflicts->map(fn ($c) => [
                    'message' => $c->message,
                    'is_blocking' => $c->isBlocking,
                ])->toArray(),
                'employees_count' => count($this->selectedEmployeeIds),
                'return_date' => Carbon::parse($this->returnDate)->format('d.m.Y'),
                'end_date' => $this->endDate ? Carbon::parse($this->endDate)->format('d.m.Y') : null,
                'vehicle' => $vehicle ? $vehicle->registration_number.' – '.$vehicle->brand.' '.$vehicle->model : null,
            ];

            $this->showPreview = true;
        } catch (ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->first() ?? 'Błąd walidacji.';
        } catch (\Exception $e) {
            $this->errorMessage = 'Błąd: '.$e->getMessage();
        }
    }

    public function saveReturn(): void
    {
        $this->errorMessage = '';

        if (empty($this->returnDate)) {
            $this->addError('returnDate', 'Wybierz datę zjazdu.');

            return;
        }

        if (empty($this->selectedEmployeeIds)) {
            $this->addError('selectedEmployeeIds', 'Wybierz co najmniej jednego pracownika.');

            return;
        }

        // Validate ticket costs for public transport
        if ($this->isPublicTransport) {
            if (empty($this->sharedStartAirportLocationId) || empty($this->sharedEndAirportLocationId)) {
                $this->addError('sharedStartAirportLocationId', 'Wybierz lotnisko startowe i docelowe dla transportu publicznego.');

                return;
            }

            foreach ($this->selectedEmployeeIds as $empId) {
                $cost = $this->ticketCostsByEmployee[$empId] ?? [];
                if (empty($cost['amount'])) {
                    $this->addError('ticketCostsByEmployee.'.$empId.'.amount', 'Uzupełnij koszt biletu.');

                    return;
                }
            }
        }

        // Save ticket file attachments temporarily
        $ticketCostsToSave = [];
        foreach ($this->selectedEmployeeIds as $empId) {
            $cost = $this->ticketCostsByEmployee[$empId] ?? [];
            $ticketCostsToSave[$empId] = [
                'amount' => $cost['amount'] ?? null,
                'currency' => $cost['currency'] ?? 'PLN',
            ];
        }

        // Store data in session for the controller to pick up
        Session::put('return_trip_v2_data', [
            'return_date' => $this->returnDate,
            'end_date' => $this->endDate ?: $this->returnDate,
            'vehicle_id' => $this->vehicleId ?: null,
            'employee_ids' => $this->selectedEmployeeIds,
            'notes' => $this->notes,
            'is_public_transport' => $this->isPublicTransport,
            'ticket_costs_per_employee' => $ticketCostsToSave,
            'start_airport_location_id' => $this->sharedStartAirportLocationId,
            'end_airport_location_id' => $this->sharedEndAirportLocationId,
        ]);

        $this->redirect(route('return-trips.store-v2'), navigate: false);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.return-trip-planner');
    }
}
