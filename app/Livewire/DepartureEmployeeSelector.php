<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Services\AssignmentQueryService;
use Livewire\Component;
use Carbon\Carbon;

class DepartureEmployeeSelector extends Component
{
    public $departureDate;
    public $selectedEmployeeIds = [];
    public $employees = [];

    protected $listeners = ['dateChanged' => 'updateEmployees'];

    public function mount($departureDate = null, $selectedEmployeeIds = [])
    {
        $this->departureDate = $departureDate ?? date('Y-m-d');
        $this->selectedEmployeeIds = $selectedEmployeeIds;
        $this->updateEmployees();
    }

    public function updatedDepartureDate()
    {
        $this->updateEmployees();
        $this->selectedEmployeeIds = []; // Reset selection when date changes
    }

    public function updateEmployees()
    {
        if (!$this->departureDate) {
            $this->employees = [];
            return;
        }

        try {
            $date = Carbon::parse($this->departureDate);
            $this->employees = app(AssignmentQueryService::class)
                ->getAvailableEmployeesForDeparture($date)
                ->map(function ($employee) use ($date) {
                    // Load rotation info
                    $rotation = $employee->getActiveRotationForDate($date);
                    
                    return [
                        'id' => $employee->id,
                        'full_name' => $employee->full_name,
                        'rotation' => $rotation ? [
                            'start_date' => $rotation->start_date->format('Y-m-d'),
                            'end_date' => $rotation->end_date ? $rotation->end_date->format('Y-m-d') : null,
                        ] : null,
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            $this->employees = [];
        }
    }

    public function render()
    {
        return view('livewire.departure-employee-selector');
    }
}
