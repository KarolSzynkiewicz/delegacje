<?php

namespace App\View\Components\Logistics;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

/**
 * Siatka miejsc (kierowca + pasażerowie) współdzielona przez planery wyjazdu i zjazdu.
 * Akcje Livewire (assignDriverSeatEmployee, toggleExternalDriver) pozostają w rodzicu.
 */
class VehicleSeatGrid extends Component
{
    public Collection $selectedEmployees;

    public int $capacity;

    /** @var array<int, object|null> passenger slot index => employee model or null */
    public array $passengerSlots;

    public bool $isExternalDriver;

    public int $driverEmployeeId;

    public Collection $driverCandidates;

    public bool $isMissingDriver;

    public bool $isOverCapacity;

    public int $totalTripPeople;

    /** @var object|null */
    public $driverEmployee;

    public function __construct(
        public ?object $vehicle,
        public array $vehicleSeats,
        mixed $selectedEmployees,
        public string $wireKeyPrefix = 'lvs',
        public bool $interactive = true,
    ) {
        $this->selectedEmployees = $selectedEmployees instanceof Collection
            ? $selectedEmployees
            : collect($selectedEmployees);
        $this->computeLayout();
    }

    protected function computeLayout(): void
    {
        if ($this->vehicleSeats === []) {
            $this->capacity = (int) ($this->vehicle?->capacity ?? 1);
            $this->passengerSlots = [];
            $this->isExternalDriver = true;
            $this->driverEmployeeId = 0;
            $this->driverCandidates = collect();
            $this->totalTripPeople = 0;
            $this->isMissingDriver = false;
            $this->isOverCapacity = false;
            $this->driverEmployee = null;

            return;
        }

        $driverSeat = $this->vehicleSeats[0] ?? null;
        $this->isExternalDriver = (bool) ($driverSeat['external_driver'] ?? true);
        $this->driverEmployeeId = (int) ($driverSeat['employee_id'] ?? 0);
        $occupiedByDriver = $this->driverEmployeeId;

        $this->capacity = (int) ($this->vehicle?->capacity ?? max(1, count($this->vehicleSeats)));

        $assignedPassengers = [];
        foreach ($this->vehicleSeats as $si => $s) {
            if ($si > 0) {
                $assignedPassengers[$si] = (int) ($s['employee_id'] ?? 0);
            }
        }

        $passengerPool = $this->selectedEmployees->filter(fn ($e) => $e->id !== $occupiedByDriver)->values();

        $occupiedFromSeats = [];
        foreach ($assignedPassengers as $empId) {
            if ($empId && $empId !== $occupiedByDriver) {
                $emp = $passengerPool->firstWhere('id', $empId);
                if ($emp) {
                    $occupiedFromSeats[] = $emp;
                }
            }
        }

        $seatedIds = array_column($occupiedFromSeats, 'id');
        foreach ($passengerPool as $emp) {
            if (! in_array($emp->id, $seatedIds, true)) {
                $occupiedFromSeats[] = $emp;
            }
        }

        $this->passengerSlots = [];
        for ($pi = 1; $pi < $this->capacity; $pi++) {
            $this->passengerSlots[$pi] = $occupiedFromSeats[$pi - 1] ?? null;
        }

        $this->driverCandidates = $passengerPool;
        $this->totalTripPeople = $this->selectedEmployees->count() + ($this->isExternalDriver ? 1 : 0);
        $this->isMissingDriver = (! $this->isExternalDriver) && $this->driverEmployeeId === 0;
        $this->isOverCapacity = $this->totalTripPeople > $this->capacity;
        $this->driverEmployee = (! $this->isExternalDriver && $this->driverEmployeeId)
            ? $this->selectedEmployees->firstWhere('id', $this->driverEmployeeId)
            : null;
    }

    public function render(): View
    {
        return view('components.logistics.vehicle-seat-grid');
    }
}
