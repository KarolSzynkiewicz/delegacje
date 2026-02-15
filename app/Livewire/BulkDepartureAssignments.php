<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Role;
use App\Models\Vehicle;
use App\Models\Accommodation;
use Carbon\Carbon;

class BulkDepartureAssignments extends Component
{
    // Store only IDs (serializable)
    public $employeeIds = [];
    public $projectIds = [];
    public $roleIds = [];
    public $vehicleIds = [];
    public $accommodationIds = [];
    
    public $arrivalDate;
    public $weekEnd;
    
    public $assignments = [];
    public $validationErrors = [];
    
    public function mount($employeeIds, $arrivalDate, $weekEnd, $projectIds, $roleIds, $vehicleIds, $accommodationIds)
    {
        $this->employeeIds = $employeeIds;
        $this->projectIds = $projectIds;
        $this->roleIds = $roleIds;
        $this->vehicleIds = $vehicleIds;
        $this->accommodationIds = $accommodationIds;
        
        $this->arrivalDate = $arrivalDate;
        $this->weekEnd = $weekEnd;
        
        // Initialize assignments with default values
        foreach ($employeeIds as $employeeId) {
            $this->assignments[$employeeId] = [
                'project_id' => '',
                'role_id' => '',
                'project_start_date' => $arrivalDate,
                'project_end_date' => $weekEnd,
                
                'vehicle_id' => '',
                'position' => 'passenger',
                'vehicle_start_date' => $arrivalDate,
                'vehicle_end_date' => $weekEnd,
                
                'accommodation_id' => '',
                'accommodation_start_date' => $arrivalDate,
                'accommodation_end_date' => $weekEnd,
            ];
        }
        
        $this->validateAllAssignments();
    }
    
    public function updated($propertyName)
    {
        $this->validateAllAssignments();
    }
    
    public function validateAllAssignments()
    {
        $this->validationErrors = [];
        
        // Load employees for validation
        $employees = Employee::with('roles')->findMany($this->employeeIds);
        
        // Track vehicle usage in this form (for driver conflicts)
        $vehicleDrivers = []; // [vehicle_id => [employee_id, ...]]
        $vehicleUsage = []; // [vehicle_id => count]
        $accommodationUsage = []; // [accommodation_id => count]
        
        // First pass: collect usage data
        foreach ($employees as $employee) {
            $assignment = $this->assignments[$employee->id];
            
            if (!empty($assignment['vehicle_id'])) {
                $vehicleId = $assignment['vehicle_id'];
                
                if (!isset($vehicleUsage[$vehicleId])) {
                    $vehicleUsage[$vehicleId] = 0;
                    $vehicleDrivers[$vehicleId] = [];
                }
                
                $vehicleUsage[$vehicleId]++;
                
                if ($assignment['position'] === 'driver') {
                    $vehicleDrivers[$vehicleId][] = $employee->full_name;
                }
            }
            
            if (!empty($assignment['accommodation_id'])) {
                $accommodationId = $assignment['accommodation_id'];
                
                if (!isset($accommodationUsage[$accommodationId])) {
                    $accommodationUsage[$accommodationId] = 0;
                }
                
                $accommodationUsage[$accommodationId]++;
            }
        }
        
        // Second pass: validate each assignment
        foreach ($employees as $employee) {
            $assignment = $this->assignments[$employee->id];
            $errors = [];
            
            // Validate project
            if (empty($assignment['project_id'])) {
                $errors[] = "PROJEKT: Nie wybrano projektu";
            } elseif (empty($assignment['role_id'])) {
                $errors[] = "PROJEKT: Nie wybrano roli";
            } else {
                $role = Role::find($assignment['role_id']);
                if ($role && !$employee->roles->contains('id', $role->id)) {
                    $errors[] = "PROJEKT: Pracownik nie ma roli {$role->name}";
                }
                
                $startDate = Carbon::parse($assignment['project_start_date']);
                $endDate = Carbon::parse($assignment['project_end_date']);
                
                if ($endDate->lt($startDate)) {
                    $errors[] = "PROJEKT: Data końca przed datą początku";
                }
                
                // Check overlaps
                $overlaps = $employee->assignments()
                    ->where('is_cancelled', false)
                    ->where(function($q) use ($startDate, $endDate) {
                        $q->whereBetween('start_date', [$startDate, $endDate])
                          ->orWhereBetween('end_date', [$startDate, $endDate])
                          ->orWhere(function($q2) use ($startDate, $endDate) {
                              $q2->where('start_date', '<=', $startDate)
                                 ->where(function($q3) use ($endDate) {
                                     $q3->whereNull('end_date')
                                        ->orWhere('end_date', '>=', $endDate);
                                 });
                          });
                    })
                    ->exists();
                
                if ($overlaps) {
                    $errors[] = "PROJEKT: Nakładające się przypisanie";
                }
            }
            
            // Validate vehicle
            if (empty($assignment['vehicle_id'])) {
                $errors[] = "AUTO: Nie wybrano pojazdu";
            } else {
                $vehicleId = $assignment['vehicle_id'];
                $vehicle = Vehicle::find($vehicleId);
                
                $startDate = Carbon::parse($assignment['vehicle_start_date']);
                $endDate = Carbon::parse($assignment['vehicle_end_date']);
                
                if ($endDate->lt($startDate)) {
                    $errors[] = "AUTO: Data końca przed datą początku";
                }
                
                // Check if multiple drivers in same vehicle
                if ($assignment['position'] === 'driver' && count($vehicleDrivers[$vehicleId]) > 1) {
                    $otherDrivers = array_filter($vehicleDrivers[$vehicleId], fn($name) => $name !== $employee->full_name);
                    $errors[] = "AUTO: Konflikt kierowców - " . implode(', ', $otherDrivers);
                }
                
                // Check vehicle capacity
                if ($vehicle && $vehicleUsage[$vehicleId] > $vehicle->capacity) {
                    $errors[] = "AUTO: Przekroczona pojemność ({$vehicleUsage[$vehicleId]}/{$vehicle->capacity})";
                }
            }
            
            // Validate accommodation
            if (empty($assignment['accommodation_id'])) {
                $errors[] = "DOM: Nie wybrano zakwaterowania";
            } else {
                $accommodationId = $assignment['accommodation_id'];
                $accommodation = Accommodation::find($accommodationId);
                
                $startDate = Carbon::parse($assignment['accommodation_start_date']);
                $endDate = Carbon::parse($assignment['accommodation_end_date']);
                
                if ($endDate->lt($startDate)) {
                    $errors[] = "DOM: Data końca przed datą początku";
                }
                
                // Check accommodation capacity
                if ($accommodation && $accommodationUsage[$accommodationId] > $accommodation->capacity) {
                    $errors[] = "DOM: Przekroczona pojemność ({$accommodationUsage[$accommodationId]}/{$accommodation->capacity})";
                }
            }
            
            if (!empty($errors)) {
                $this->validationErrors[$employee->id] = [
                    'name' => $employee->full_name,
                    'errors' => $errors,
                ];
            }
        }
    }
    
    /**
     * Copy project assignment from first employee to all others.
     */
    public function copyProjectFromFirst()
    {
        if (empty($this->employeeIds)) {
            return;
        }
        
        $firstEmployeeId = $this->employeeIds[0];
        $firstAssignment = $this->assignments[$firstEmployeeId];
        
        $copiedCount = 0;
        foreach ($this->employeeIds as $employeeId) {
            if ($employeeId !== $firstEmployeeId) {
                $this->assignments[$employeeId]['project_id'] = $firstAssignment['project_id'];
                $this->assignments[$employeeId]['role_id'] = $firstAssignment['role_id'];
                $this->assignments[$employeeId]['project_start_date'] = $firstAssignment['project_start_date'];
                $this->assignments[$employeeId]['project_end_date'] = $firstAssignment['project_end_date'];
                $copiedCount++;
            }
        }
        
        $this->validateAllAssignments();
        $this->dispatch('assignment-copied', message: "Skopiowano projekt do {$copiedCount} pracowników");
    }
    
    /**
     * Copy vehicle assignment from first employee to all others.
     */
    public function copyVehicleFromFirst()
    {
        if (empty($this->employeeIds)) {
            return;
        }
        
        $firstEmployeeId = $this->employeeIds[0];
        $firstAssignment = $this->assignments[$firstEmployeeId];
        
        $copiedCount = 0;
        foreach ($this->employeeIds as $employeeId) {
            if ($employeeId !== $firstEmployeeId) {
                $this->assignments[$employeeId]['vehicle_id'] = $firstAssignment['vehicle_id'];
                $this->assignments[$employeeId]['position'] = $firstAssignment['position'];
                $this->assignments[$employeeId]['vehicle_start_date'] = $firstAssignment['vehicle_start_date'];
                $this->assignments[$employeeId]['vehicle_end_date'] = $firstAssignment['vehicle_end_date'];
                $copiedCount++;
            }
        }
        
        $this->validateAllAssignments();
        $this->dispatch('assignment-copied', message: "Skopiowano pojazd do {$copiedCount} pracowników");
    }
    
    /**
     * Copy accommodation assignment from first employee to all others.
     */
    public function copyAccommodationFromFirst()
    {
        if (empty($this->employeeIds)) {
            return;
        }
        
        $firstEmployeeId = $this->employeeIds[0];
        $firstAssignment = $this->assignments[$firstEmployeeId];
        
        $copiedCount = 0;
        foreach ($this->employeeIds as $employeeId) {
            if ($employeeId !== $firstEmployeeId) {
                $this->assignments[$employeeId]['accommodation_id'] = $firstAssignment['accommodation_id'];
                $this->assignments[$employeeId]['accommodation_start_date'] = $firstAssignment['accommodation_start_date'];
                $this->assignments[$employeeId]['accommodation_end_date'] = $firstAssignment['accommodation_end_date'];
                $copiedCount++;
            }
        }
        
        $this->validateAllAssignments();
        $this->dispatch('assignment-copied', message: "Skopiowano zakwaterowanie do {$copiedCount} pracowników");
    }
    
    public function render()
    {
        // Load collections fresh for each render (not stored in state)
        $employees = Employee::with('roles')->findMany($this->employeeIds);
        $projects = Project::findMany($this->projectIds);
        $roles = Role::findMany($this->roleIds);
        $vehicles = Vehicle::findMany($this->vehicleIds);
        $accommodations = Accommodation::findMany($this->accommodationIds);
        
        return view('livewire.bulk-departure-assignments', [
            'employees' => $employees,
            'projects' => $projects,
            'roles' => $roles,
            'vehicles' => $vehicles,
            'accommodations' => $accommodations,
        ]);
    }
}
