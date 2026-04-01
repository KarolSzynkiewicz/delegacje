<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Vehicle;
use App\Models\LogisticsEvent;
use App\Models\LogisticsEventParticipant;
use App\Models\Adjustment;
use App\Enums\LogisticsEventType;
use App\Enums\LogisticsEventStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TransferPlanner extends Component
{
    // Basic
    public string $transferDate = '';
    public ?int $fromLocationId = null;
    public ?int $toLocationId = null;
    public ?int $vehicleId = null;
    public string $notes = '';

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

    public function mount(): void
    {
        $this->transferDate = now()->format('Y-m-d\TH:i');
    }

    public function getEmployeesProperty()
    {
        return Employee::orderBy('last_name')->orderBy('first_name')->get();
    }

    public function getFilteredEmployeesProperty()
    {
        return $this->employees->filter(function (Employee $e) {
            if (!$this->employeeSearch) {
                return true;
            }
            $q = mb_strtolower($this->employeeSearch);
            return str_contains(mb_strtolower($e->full_name), $q)
                || str_contains(mb_strtolower($e->phone ?? ''), $q);
        });
    }

    public function getLocationsProperty()
    {
        return Location::orderBy('name')->get();
    }

    public function getVehiclesProperty()
    {
        return Vehicle::where('type', 'company_vehicle')
            ->orderBy('registration_number')
            ->get()
            ->filter(function (Vehicle $v) {
                if (!$this->vehicleSearch) {
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
        if (!$this->driverEmployeeId) {
            return collect();
        }
        return \App\Models\Payroll::with('employee')
            ->where('employee_id', $this->driverEmployeeId)
            ->orderBy('period_start', 'desc')
            ->get();
    }

    public function updatedDriverEmployeeId(): void
    {
        // Reset payroll selection when driver changes
        $this->driverPayrollId = null;
    }

    public function toggleEmployee(int $employeeId): void
    {
        if (in_array($employeeId, $this->selectedEmployeeIds)) {
            $this->selectedEmployeeIds = array_values(
                array_filter($this->selectedEmployeeIds, fn($id) => $id !== $employeeId)
            );
            // If removed employee was the driver, clear driver
            if ($this->driverEmployeeId === $employeeId) {
                $this->driverEmployeeId = null;
                $this->driverPayrollId = null;
            }
        } else {
            $this->selectedEmployeeIds[] = $employeeId;
        }
    }

    public function save(): void
    {
        $this->validate([
            'transferDate'           => ['required', 'date'],
            'fromLocationId'         => ['required', 'exists:locations,id'],
            'toLocationId'           => ['required', 'exists:locations,id', 'different:fromLocationId'],
            'vehicleId'              => ['nullable', 'exists:vehicles,id'],
            'selectedEmployeeIds'    => ['required', 'array', 'min:1'],
            'selectedEmployeeIds.*'  => ['exists:employees,id'],
            'driverEmployeeId'       => ['nullable', 'in:' . implode(',', $this->selectedEmployeeIds ?: [0])],
            'driverPaymentAmount'    => ['nullable', 'numeric', 'min:0'],
            'driverPaymentCurrency'  => ['required_with:driverPaymentAmount', 'size:3'],
            'driverPayrollId'        => [
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
            'transferDate.required'        => 'Data transferu jest wymagana.',
            'fromLocationId.required'      => 'Wybierz lokalizację startową.',
            'toLocationId.required'        => 'Wybierz lokalizację docelową.',
            'toLocationId.different'       => 'Lokalizacja docelowa musi być inna niż startowa.',
            'selectedEmployeeIds.required' => 'Dodaj co najmniej jednego uczestnika.',
            'selectedEmployeeIds.min'      => 'Dodaj co najmniej jednego uczestnika.',
            'driverPaymentCurrency.size'   => 'Waluta musi mieć dokładnie 3 znaki (np. PLN, EUR).',
        ]);

        DB::transaction(function () {
            $event = LogisticsEvent::create([
                'type'             => LogisticsEventType::TRANSFER,
                'event_date'       => $this->transferDate,
                'end_date'         => $this->transferDate,
                'from_location_id' => $this->fromLocationId,
                'to_location_id'   => $this->toLocationId,
                'vehicle_id'       => $this->vehicleId ?: null,
                'has_transport'    => empty($this->vehicleId),
                'status'           => LogisticsEventStatus::PLANNED,
                'notes'            => $this->notes ?: null,
                'created_by'       => Auth::id(),
            ]);

            foreach ($this->selectedEmployeeIds as $employeeId) {
                LogisticsEventParticipant::create([
                    'logistics_event_id' => $event->id,
                    'employee_id'        => $employeeId,
                    'status'             => 'pending',
                ]);
            }

            if ($this->driverEmployeeId && $this->driverPaymentAmount !== '' && $this->driverPaymentAmount > 0) {
                Adjustment::create([
                    'employee_id'        => $this->driverEmployeeId,
                    'logistics_event_id' => $event->id,
                    'payroll_id'         => $this->driverPayrollId ?: null,
                    'amount'             => $this->driverPaymentAmount,
                    'currency'           => strtoupper(trim($this->driverPaymentCurrency)),
                    'type'               => 'bonus',
                    'date'               => \Carbon\Carbon::parse($this->transferDate)->toDateString(),
                    'notes'              => 'Wynagrodzenie za transfer',
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
