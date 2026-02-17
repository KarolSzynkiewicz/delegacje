<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Services\AssignmentQueryService;
use Livewire\Component;
use Carbon\Carbon;

class DepartureEmployeeSelector extends Component
{
    public $departureDate;
    public $endDate;
    public $selectedEmployeeIds = [];
    public $employees = [];
    public $editMode = false;

    protected $listeners = ['dateChanged' => 'updateEmployees'];
    
    public function getIsDateInPastProperty()
    {
        if (!$this->departureDate) {
            return false;
        }
        
        $today = Carbon::today();
        $departure = Carbon::parse($this->departureDate)->startOfDay();
        
        // Dziś nie jest w przeszłości - sprawdzamy czy data jest wcześniejsza niż dzisiaj
        return $departure->lt($today);
    }
    
    protected function isDateInPast()
    {
        return $this->isDateInPast;
    }

    public function mount($departureDate = null, $selectedEmployeeIds = [], $endDate = null)
    {
        $this->departureDate = $departureDate ?? date('Y-m-d');
        $this->endDate = $endDate;
        $this->selectedEmployeeIds = $selectedEmployeeIds;
        $this->editMode = !empty($selectedEmployeeIds);
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
            
            // Get available employees (not assigned to projects)
            $availableEmployees = app(AssignmentQueryService::class)
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
                });
            
            // In edit mode, also include currently selected employees
            if ($this->editMode && !empty($this->selectedEmployeeIds)) {
                $selectedEmployees = Employee::whereIn('id', $this->selectedEmployeeIds)
                    ->get()
                    ->map(function ($employee) use ($date) {
                        $rotation = $employee->getActiveRotationForDate($date);
                        
                        return [
                            'id' => $employee->id,
                            'full_name' => $employee->full_name . ' (obecnie wybrany)',
                            'rotation' => $rotation ? [
                                'start_date' => $rotation->start_date->format('Y-m-d'),
                                'end_date' => $rotation->end_date ? $rotation->end_date->format('Y-m-d') : null,
                            ] : null,
                        ];
                    });
                
                // Merge selected with available (remove duplicates)
                $allEmployees = $selectedEmployees->merge(
                    $availableEmployees->filter(function($emp) {
                        return !in_array($emp['id'], $this->selectedEmployeeIds);
                    })
                );
                
                $this->employees = $allEmployees->toArray();
            } else {
                $this->employees = $availableEmployees->toArray();
            }
        } catch (\Exception $e) {
            $this->employees = [];
        }
    }

    public function render()
    {
        return view('livewire.departure-employee-selector');
    }
}
