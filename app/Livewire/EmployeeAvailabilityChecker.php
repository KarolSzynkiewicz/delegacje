<?php

namespace App\Livewire;

use App\Models\Employee;
use Livewire\Component;

class EmployeeAvailabilityChecker extends Component
{
    public $employeeId;
    public $startDate;
    public $endDate;
    public $isAvailable = null;
    public $conflicts = [];

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['employeeId', 'startDate', 'endDate'])) {
            $this->checkAvailability();
        }
    }

    public function checkAvailability()
    {
        if (!$this->employeeId || !$this->startDate) {
            $this->isAvailable = null;
            return;
        }

        $employee = Employee::find($this->employeeId);
        if (!$employee) return;

        $endDate = $this->endDate ?: $this->startDate;
        
        $this->isAvailable = $employee->isAvailableInDateRange($this->startDate, $endDate);
        
        if (!$this->isAvailable) {
            $this->conflicts = $employee->assignments()
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereBetween('start_date', [$this->startDate, $this->endDate ?: $this->startDate])
                        ->orWhereBetween('end_date', [$this->startDate, $this->endDate ?: $this->startDate]);
                })
                ->with('project')
                ->get();
        } else {
            $this->conflicts = [];
        }
    }

    public function render()
    {
        return view('livewire.employee-availability-checker');
    }
}
