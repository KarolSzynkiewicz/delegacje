<?php

namespace App\Livewire;

use App\Services\AssignmentQueryService;
use Livewire\Component;
use Carbon\Carbon;

class ReturnTripEmployeeSelector extends Component
{
    public $returnDate;
    public $selectedEmployeeIds = [];
    public $employees = [];
    public $returnTripId = null; // ID zjazdu podczas edycji

    public function mount($returnDate = null, $selectedEmployeeIds = [], $returnTripId = null)
    {
        $this->returnDate = $returnDate ?? date('Y-m-d');
        $this->selectedEmployeeIds = $selectedEmployeeIds;
        $this->returnTripId = $returnTripId;
        $this->updateEmployees();
    }

    public function updatedReturnDate()
    {
        $this->updateEmployees();
        // Don't reset selection when date changes during edit - user might want to keep selection
        if (!$this->returnTripId) {
            $this->selectedEmployeeIds = [];
        }
    }

    public function updateEmployees()
    {
        if (!$this->returnDate) {
            $this->employees = [];
            return;
        }

        try {
            $date = Carbon::parse($this->returnDate);
            
            // If editing a return trip, we need to show employees who WOULD BE in projects
            // on the selected date, assuming the return trip doesn't exist (restored state)
            if ($this->returnTripId) {
                $this->employees = $this->getEmployeesForEditMode($date);
            } else {
                // Normal mode - show employees with active assignments
                $this->employees = app(AssignmentQueryService::class)
                    ->getEmployeesWithActiveAssignments($date)
                    ->map(function ($employee) {
                        return [
                            'id' => $employee->id,
                            'full_name' => $employee->full_name,
                            'project' => $employee->assignments->first()?->project->name ?? null,
                        ];
                    })
                    ->toArray();
            }
        } catch (\Exception $e) {
            $this->employees = [];
        }
    }
    
    /**
     * Get employees who WOULD BE in projects on the given date, assuming return trip doesn't exist.
     * 
     * During edit mode, we show:
     * 1. All employees with active assignments on the selected date (normal)
     * 2. PLUS employees from the return trip whose assignments were shortened (end_date = return trip date)
     *    and would be active on the new date if end_date was restored to original_end_date
     */
    protected function getEmployeesForEditMode(Carbon $date): array
    {
        $returnTrip = \App\Models\LogisticsEvent::with(['participants.assignment'])->find($this->returnTripId);
        
        if (!$returnTrip || $returnTrip->type !== \App\Enums\LogisticsEventType::RETURN) {
            // Fallback to normal mode
            return app(AssignmentQueryService::class)
                ->getEmployeesWithActiveAssignments($date)
                ->map(function ($employee) {
                    return [
                        'id' => $employee->id,
                        'full_name' => $employee->full_name,
                        'project' => $employee->assignments->first()?->project->name ?? null,
                    ];
                })
                ->toArray();
        }
        
        $returnTripDate = Carbon::parse($returnTrip->event_date)->startOfDay();
        $selectedDate = $date->startOfDay();
        
        // 1. Get all employees with active assignments on the selected date (normal)
        // This includes employees who are NOT affected by the return trip
        $normalEmployees = app(AssignmentQueryService::class)
            ->getEmployeesWithActiveAssignments($date)
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'full_name' => $employee->full_name,
                    'project' => $employee->assignments->first()?->project->name ?? null,
                ];
            })
            ->keyBy('id')
            ->toArray();
        
        // 2. Get employees from return trip whose assignments were shortened
        // Check if their assignments (with original_end_date restored) would be active on selected date
        $shortenedEmployeeIds = [];
        foreach ($returnTrip->participants as $participant) {
            // Skip return trip vehicle assignments (is_return_trip = true)
            if ($participant->assignment_type === 'vehicle_assignment' && $participant->assignment) {
                $vehicleAssignment = $participant->assignment;
                if ($vehicleAssignment->is_return_trip ?? false) {
                    continue;
                }
            }
            
            // Check if assignment with original_end_date would be active on selected date
            if (!$participant->assignment) {
                continue;
            }
            
            $assignmentStartDate = Carbon::parse($participant->assignment->start_date)->startOfDay();
            
            // Check if assignment would be active on selected date with original_end_date restored
            if ($assignmentStartDate->lte($selectedDate)) {
                $wouldBeActive = false;
                
                if ($participant->original_end_date !== null) {
                    // Original end_date was set - check if it's >= selectedDate
                    $originalEndDate = Carbon::parse($participant->original_end_date)->startOfDay();
                    $wouldBeActive = $originalEndDate->gte($selectedDate);
                } else {
                    // Original end_date was null (indefinite) - would be active
                    $wouldBeActive = true;
                }
                
                if ($wouldBeActive) {
                    $shortenedEmployeeIds[] = $participant->employee_id;
                }
            }
        }
        
        // Get unique employee IDs
        $shortenedEmployeeIds = array_unique($shortenedEmployeeIds);
        
        // Load these employees with their assignments (checking what they WOULD have with original_end_date)
        $shortenedEmployees = [];
        if (!empty($shortenedEmployeeIds)) {
            $employees = \App\Models\Employee::whereIn('id', $shortenedEmployeeIds)
                ->with(['assignments.project', 'accommodationAssignments.accommodation'])
                ->get();
            
            foreach ($employees as $employee) {
                // Find the project from assignments that would be active on selected date
                // (considering original_end_date from participants)
                $projectName = null;
                foreach ($returnTrip->participants as $participant) {
                    if ($participant->employee_id === $employee->id && 
                        $participant->assignment_type === 'project_assignment' &&
                        $participant->assignment) {
                        $assignmentStartDate = Carbon::parse($participant->assignment->start_date)->startOfDay();
                        if ($assignmentStartDate->lte($selectedDate)) {
                            $wouldBeActive = false;
                            if ($participant->original_end_date !== null) {
                                $originalEndDate = Carbon::parse($participant->original_end_date)->startOfDay();
                                $wouldBeActive = $originalEndDate->gte($selectedDate);
                            } else {
                                $wouldBeActive = true;
                            }
                            
                            if ($wouldBeActive && $participant->assignment->project) {
                                $projectName = $participant->assignment->project->name;
                                break;
                            }
                        }
                    }
                }
                
                $shortenedEmployees[$employee->id] = [
                    'id' => $employee->id,
                    'full_name' => $employee->full_name,
                    'project' => $projectName,
                ];
            }
        }
        
        // Merge both lists (normal employees + shortened employees)
        // Use keyBy to ensure unique employees (by ID)
        $allEmployees = [];
        foreach ($normalEmployees as $id => $employee) {
            $allEmployees[$id] = $employee;
        }
        foreach ($shortenedEmployees as $id => $employee) {
            // Only add if not already present (avoid duplicates)
            if (!isset($allEmployees[$id])) {
                $allEmployees[$id] = $employee;
            }
        }
        
        // Sort by name
        usort($allEmployees, function ($a, $b) {
            return strcmp($a['full_name'], $b['full_name']);
        });
        
        return array_values($allEmployees);
    }

    public function render()
    {
        return view('livewire.return-trip-employee-selector');
    }
}
