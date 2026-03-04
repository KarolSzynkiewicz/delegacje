<?php

namespace App\Livewire;

use App\Services\DeparturePlannerService;
use Livewire\Component;
use Livewire\Attributes\On;
use Carbon\Carbon;

class EmployeeAssignmentModal extends Component
{
    public $show = false;
    public $employee = null;
    public $project = null;
    public $role = null;
    public $arrivalDate = null;
    
    public $employeeAvailability = [];
    public $selectedStartDate = null;
    public $selectedEndDate = null;
    public $dateSelectionMode = 'start';
    
    protected $departurePlannerService;
    
    public function boot(DeparturePlannerService $departurePlannerService)
    {
        $this->departurePlannerService = $departurePlannerService;
    }
    
    #[On('open-employee-modal')]
    public function openModal($data)
    {
        // Handle employee - should be array from parent component
        $this->employee = $data['employee'] ?? null;
        
        // Project and role are passed as IDs, we'll load them when needed
        $this->project = $data['project'] ?? null;
        $this->role = $data['role'] ?? null;
        $this->arrivalDate = $data['arrivalDate'] ?? null;
        $this->employeeAvailability = $data['availability'] ?? [];
        $this->selectedStartDate = null;
        $this->selectedEndDate = null;
        $this->dateSelectionMode = 'start';
        $this->show = true;
    }
    
    public function updatedShow($value)
    {
        if ($value && $this->employee && $this->project && $this->role && $this->arrivalDate && empty($this->employeeAvailability)) {
            $this->loadEmployeeAvailability();
        }
    }
    
    private function loadEmployeeAvailability()
    {
        if (!$this->employee || !$this->project || !$this->role || !$this->arrivalDate) {
            $this->employeeAvailability = [];
            return;
        }
        
        // Handle employee - can be array or ID
        $employeeId = is_array($this->employee) ? ($this->employee['id'] ?? null) : $this->employee;
        if (!$employeeId) {
            $this->employeeAvailability = [];
            return;
        }
        
        $employee = \App\Models\Employee::find($employeeId);
        if (!$employee) {
            $this->employeeAvailability = [];
            return;
        }
        
        // Handle project - can be array, object, or ID
        $projectId = null;
        if (is_array($this->project)) {
            $projectId = $this->project['id'] ?? null;
        } elseif (is_object($this->project)) {
            $projectId = $this->project->id ?? null;
        } else {
            $projectId = $this->project;
        }
        
        $project = \App\Models\Project::find($projectId);
        if (!$project) {
            $this->employeeAvailability = [];
            return;
        }
        
        // Handle role - can be array, object, or ID
        $roleId = null;
        if (is_array($this->role)) {
            $roleId = $this->role['id'] ?? null;
        } elseif (is_object($this->role)) {
            $roleId = $this->role->id ?? null;
        } else {
            $roleId = $this->role;
        }
        
        $role = \App\Models\Role::find($roleId);
        if (!$role) {
            $this->employeeAvailability = [];
            return;
        }
        
        $arrivalDate = Carbon::parse($this->arrivalDate);
        
        $this->employeeAvailability = $this->departurePlannerService->getEmployeeAvailabilityForMonth(
            $employee,
            $project,
            $role,
            $arrivalDate
        );
        
        $this->selectedStartDate = null;
        $this->selectedEndDate = null;
        $this->dateSelectionMode = 'start';
    }
    
    public function selectDate($date)
    {
        if (!$this->employee || !$this->project || !$this->role) {
            return;
        }
        
        $dateCarbon = Carbon::parse($date);
        
        if ($this->dateSelectionMode === 'start') {
            $this->selectedStartDate = $date;
            $this->selectedEndDate = null;
            $this->dateSelectionMode = 'end';
        } else {
            // End date selection
            if ($dateCarbon->lt(Carbon::parse($this->selectedStartDate))) {
                // If end date is before start date, reset to start date selection
                $this->selectedStartDate = $date;
                $this->selectedEndDate = null;
                $this->dateSelectionMode = 'end';
            } else {
                $this->selectedEndDate = $date;
                $this->dateSelectionMode = 'start';
            }
        }
    }
    
    #[On('close-employee-modal')]
    public function close()
    {
        $this->show = false;
        $this->selectedStartDate = null;
        $this->selectedEndDate = null;
        $this->dateSelectionMode = 'start';
        $this->employeeAvailability = [];
        $this->employee = null;
        $this->project = null;
        $this->role = null;
        $this->arrivalDate = null;
    }
    
    public function confirmAssignment()
    {
        if (!$this->selectedStartDate || !$this->employee || !$this->project || !$this->role) {
            return;
        }
        
        $startDate = $this->selectedStartDate;
        $endDate = $this->selectedEndDate ?? $this->selectedStartDate;
        
        // Handle employee - can be array or ID
        $employeeId = is_array($this->employee) ? ($this->employee['id'] ?? null) : $this->employee;
        
        // Handle project - can be array, object, or ID
        $projectId = null;
        if (is_array($this->project)) {
            $projectId = $this->project['id'] ?? null;
        } elseif (is_object($this->project)) {
            $projectId = $this->project->id ?? null;
        } else {
            $projectId = $this->project;
        }
        
        // Handle role - can be array, object, or ID
        $roleId = null;
        if (is_array($this->role)) {
            $roleId = $this->role['id'] ?? null;
        } elseif (is_object($this->role)) {
            $roleId = $this->role->id ?? null;
        } else {
            $roleId = $this->role;
        }
        
        if (!$employeeId || !$projectId || !$roleId) {
            return;
        }
        
        // Dispatch event to parent component
        $this->dispatch('assignment-confirmed', [
            'employee_id' => $employeeId,
            'project_id' => $projectId,
            'role_id' => $roleId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
        
        $this->close();
    }
    
    public function render()
    {
        return view('livewire.employee-assignment-modal');
    }
}
