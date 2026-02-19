<?php

namespace App\Livewire;

use App\Models\Vehicle;
use App\Services\VehicleValidationService;
use Livewire\Component;
use Carbon\Carbon;

class VehicleAvailabilityChecker extends Component
{
    public $vehicleId;
    public $departureDate;
    public $endDate;
    public $validationErrors = [];
    public $conflicts = [];

    protected $listeners = [
        'checkVehicleAvailability' => 'checkAvailability',
        'dateChanged' => 'handleDateChanged'
    ];
    
    public function handleDateChanged($data)
    {
        if (isset($data['departureDate'])) {
            $this->departureDate = $data['departureDate'];
        }
        if (isset($data['endDate'])) {
            $this->endDate = $data['endDate'];
        }
        $this->checkAvailability();
    }

    public function updatedVehicleId()
    {
        $this->checkAvailability();
    }

    public function updatedDepartureDate()
    {
        $this->checkAvailability();
    }

    public function updatedEndDate()
    {
        $this->checkAvailability();
    }
    
    public function mount($vehicleId = '', $departureDate = null, $endDate = null)
    {
        $this->vehicleId = $vehicleId;
        $this->departureDate = $departureDate ?? date('Y-m-d');
        $this->endDate = $endDate;
        
        if ($this->vehicleId && $this->departureDate && $this->endDate) {
            $this->checkAvailability();
        }
    }

    public function checkAvailability()
    {
        $this->validationErrors = [];
        $this->conflicts = [];

        if (!$this->vehicleId || !$this->departureDate || !$this->endDate) {
            return;
        }

        try {
            $vehicle = Vehicle::find($this->vehicleId);
            if (!$vehicle) {
                return;
            }

            $startDate = Carbon::parse($this->departureDate);
            $endDate = Carbon::parse($this->endDate);

            $validationService = app(VehicleValidationService::class);
            $result = $validationService->validateForLogisticsEvent(
                $vehicle,
                $startDate,
                $endDate
            );

            if (!$result['valid']) {
                $this->validationErrors = $result['errors'];
                $this->conflicts = $result['conflicts'];
            }
        } catch (\Exception $e) {
            $this->validationErrors = ['Wystąpił błąd podczas sprawdzania dostępności pojazdu.'];
        }
    }

    public function render()
    {
        return view('livewire.vehicle-availability-checker');
    }
}
