<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Role;
use App\Models\Vehicle;
use App\Models\Accommodation;
use App\Models\LogisticsEvent;
use App\Services\ProjectAssignmentService;
use App\Services\VehicleAssignmentService;
use App\Services\AccommodationAssignmentService;
use App\Enums\LogisticsEventType;
use App\Enums\LogisticsEventStatus;
use App\Enums\VehiclePosition;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
        $employees = Employee::with(['roles', 'rotations', 'employeeDocuments'])->findMany($this->employeeIds);
        
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
        
        // Second pass: validate each assignment using proper services
        foreach ($employees as $employee) {
            $assignment = $this->assignments[$employee->id];
            $errors = [];
            
            // ===== VALIDATE PROJECT ASSIGNMENT =====
            if (empty($assignment['project_id'])) {
                $errors[] = "PROJEKT: Nie wybrano projektu";
            } elseif (empty($assignment['role_id'])) {
                $errors[] = "PROJEKT: Nie wybrano roli";
            } else {
                try {
                    $project = Project::find($assignment['project_id']);
                    $role = Role::find($assignment['role_id']);
                    $startDate = Carbon::parse($assignment['project_start_date']);
                    $endDate = Carbon::parse($assignment['project_end_date']);
                    $arrivalDate = Carbon::parse($this->arrivalDate);
                    
                    if ($endDate->lt($startDate)) {
                        $errors[] = "PROJEKT: Data końca przed datą początku";
                    }
                    
                    // Check if start date is before arrival date
                    if ($startDate->lt($arrivalDate)) {
                        $errors[] = "PROJEKT: Start przed przyjazdem ({$arrivalDate->format('d.m.Y')})";
                    }
                    
                    // Validate using ProjectAssignmentService (this includes ALL validations)
                    // We use a try-catch to capture validation errors
                    if ($project && $role) {
                        // Check role
                        if (!$employee->hasRole($role->id)) {
                            $errors[] = "PROJEKT: Brak roli {$role->name}";
                        }
                        
                        // Check rotation coverage
                        if (!$employee->hasActiveRotationInDateRange($startDate, $endDate)) {
                            $errors[] = "PROJEKT: Brak rotacji na cały okres";
                        }
                        
                        // Check documents
                        $hasIsRequiredColumn = \Illuminate\Support\Facades\Schema::hasColumn('documents', 'is_required');
                        if ($hasIsRequiredColumn) {
                            $requiredDocuments = \App\Models\Document::where('is_required', true)->get();
                            
                            if ($requiredDocuments->isNotEmpty()) {
                                $missingDocuments = [];
                                
                                foreach ($requiredDocuments as $document) {
                                    $hasActiveDocument = $employee->employeeDocuments()
                                        ->where('document_id', $document->id)
                                        ->where(function ($q) use ($startDate, $endDate) {
                                            $q->where(function ($q2) use ($startDate, $endDate) {
                                                $q2->where('kind', 'bezokresowy')
                                                   ->where('valid_from', '<=', $endDate);
                                            })->orWhere(function ($q2) use ($startDate, $endDate) {
                                                $q2->where('kind', 'okresowy')
                                                   ->where('valid_from', '<=', $startDate)
                                                   ->where(function ($q3) use ($endDate) {
                                                       $q3->whereNull('valid_to')
                                                          ->orWhere('valid_to', '>=', $endDate);
                                                   });
                                            });
                                        })
                                        ->exists();
                                    
                                    if (!$hasActiveDocument) {
                                        $missingDocuments[] = $document->name;
                                    }
                                }
                                
                                if (!empty($missingDocuments)) {
                                    $errors[] = "PROJEKT: Brak dokumentów: " . implode(', ', $missingDocuments);
                                }
                            }
                        }
                        
                        // Check overlapping assignments
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
                        
                        // Check project dates (start/end)
                        if ($project->start_date && $startDate->lt(Carbon::parse($project->start_date))) {
                            $errors[] = "PROJEKT: Przed startem projektu ({$project->start_date->format('d.m.Y')})";
                        }
                        
                        if ($project->end_date && $endDate->gt(Carbon::parse($project->end_date))) {
                            $errors[] = "PROJEKT: Po końcu projektu ({$project->end_date->format('d.m.Y')})";
                        }
                        
                        // Check project demand
                        if (!$project->hasDemandForRoleInDateRange($role->id, $startDate, $endDate)) {
                            $errors[] = "PROJEKT: Brak zapotrzebowania na rolę";
                        }
                    }
                } catch (\Exception $e) {
                    $errors[] = "PROJEKT: " . $e->getMessage();
                }
            }
            
            // ===== VALIDATE VEHICLE ASSIGNMENT =====
            if (empty($assignment['vehicle_id'])) {
                $errors[] = "AUTO: Nie wybrano pojazdu";
            } else {
                $vehicleId = $assignment['vehicle_id'];
                $vehicle = Vehicle::find($vehicleId);
                
                $startDate = Carbon::parse($assignment['vehicle_start_date']);
                $endDate = Carbon::parse($assignment['vehicle_end_date']);
                $arrivalDate = Carbon::parse($this->arrivalDate);
                
                if ($endDate->lt($startDate)) {
                    $errors[] = "AUTO: Data końca przed datą początku";
                }
                
                // Check if start date is before arrival date
                if ($startDate->lt($arrivalDate)) {
                    $errors[] = "AUTO: Start przed przyjazdem ({$arrivalDate->format('d.m.Y')})";
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
            
            // ===== VALIDATE ACCOMMODATION ASSIGNMENT =====
            if (empty($assignment['accommodation_id'])) {
                $errors[] = "DOM: Nie wybrano zakwaterowania";
            } else {
                $accommodationId = $assignment['accommodation_id'];
                $accommodation = Accommodation::find($accommodationId);
                
                $startDate = Carbon::parse($assignment['accommodation_start_date']);
                $endDate = Carbon::parse($assignment['accommodation_end_date']);
                $arrivalDate = Carbon::parse($this->arrivalDate);
                
                if ($endDate->lt($startDate)) {
                    $errors[] = "DOM: Data końca przed datą początku";
                }
                
                // Check if start date is before arrival date
                if ($startDate->lt($arrivalDate)) {
                    $errors[] = "DOM: Start przed przyjazdem ({$arrivalDate->format('d.m.Y')})";
                }
                
                // Check accommodation capacity
                if ($accommodation && $accommodationUsage[$accommodationId] > $accommodation->capacity) {
                    $errors[] = "DOM: Przekroczona pojemność ({$accommodationUsage[$accommodationId]}/{$accommodation->capacity})";
                }
                
                // Check if accommodation is rented and lease hasn't ended
                if ($accommodation && $accommodation->is_rented && $accommodation->lease_end_date) {
                    $leaseEnd = Carbon::parse($accommodation->lease_end_date);
                    if ($endDate->gt($leaseEnd)) {
                        $errors[] = "DOM: Najem kończy się {$leaseEnd->format('d.m.Y')}";
                    }
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
    
    /**
     * Submit all assignments - this will be called from the parent view.
     */
    public function submitAssignments()
    {
        // Final validation
        $this->validateAllAssignments();
        
        // If there are validation errors, don't proceed
        if (!empty($this->validationErrors)) {
            session()->flash('error', 'Napraw błędy walidacji przed zapisaniem');
            return;
        }
        
        // Get departure data from session
        $departureData = session('pending_departure');
        
        if (!$departureData) {
            session()->flash('error', 'Brak danych wyjazdu. Rozpocznij proces od początku.');
            return redirect()->route('departures.create');
        }
        
        try {
            DB::beginTransaction();
            
            // 1. Create departure (logistics event)
            $departure = LogisticsEvent::create([
                'type' => LogisticsEventType::DEPARTURE,
                'event_date' => Carbon::parse($departureData['event_date']),
                'end_date' => Carbon::parse($departureData['end_date']),
                'from_location_id' => $departureData['from_location_id'],
                'to_location_id' => $departureData['to_location_id'],
                'vehicle_id' => $departureData['vehicle_id'] ?? null,
                'status' => LogisticsEventStatus::PLANNED,
                'notes' => $departureData['notes'] ?? null,
                'has_transport' => !empty($departureData['vehicle_id']),
                'created_by' => auth()->id(),
            ]);

            // 2. Add participants to departure
            foreach ($departureData['participants'] as $employeeId) {
                $departure->participants()->create([
                    'employee_id' => $employeeId,
                ]);
            }

            // 3. Create all assignments for each participant
            $projectAssignmentService = app(ProjectAssignmentService::class);
            $vehicleAssignmentService = app(VehicleAssignmentService::class);
            $accommodationAssignmentService = app(AccommodationAssignmentService::class);
            
            foreach ($this->assignments as $employeeId => $assignmentData) {
                $employee = Employee::findOrFail($employeeId);

                // Project assignment
                $project = Project::findOrFail($assignmentData['project_id']);
                $role = Role::findOrFail($assignmentData['role_id']);

                $projectAssignmentService->createAssignment(
                    $project,
                    $employee,
                    $role,
                    Carbon::parse($assignmentData['project_start_date']),
                    Carbon::parse($assignmentData['project_end_date']),
                    null, // notes
                    $departure->id // Link to departure!
                );

                // Vehicle assignment
                $vehicle = Vehicle::findOrFail($assignmentData['vehicle_id']);
                
                $vehicleAssignmentService->createAssignment(
                    $employee,
                    $vehicle,
                    VehiclePosition::from($assignmentData['position']),
                    Carbon::parse($assignmentData['vehicle_start_date']),
                    Carbon::parse($assignmentData['vehicle_end_date']),
                    null, // notes
                    $departure->id // Link to departure
                );

                // Accommodation assignment
                $accommodation = Accommodation::findOrFail($assignmentData['accommodation_id']);
                
                $accommodationAssignmentService->createAssignment(
                    $employee,
                    $accommodation,
                    Carbon::parse($assignmentData['accommodation_start_date']),
                    Carbon::parse($assignmentData['accommodation_end_date']),
                    null, // notes
                    $departure->id // Link to departure
                );
            }

            DB::commit();
            session()->forget('pending_departure');

            session()->flash('success', "Wyjazd oraz wszystkie przypisania zostały utworzone! ({$departure->participants->count()} pracowników)");
            
            return redirect()->route('weekly-overview.index', [
                'start_date' => Carbon::parse($departureData['end_date'])->startOfWeek()->format('Y-m-d')
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Error creating departure with assignments', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            session()->flash('error', 'Wystąpił błąd: ' . $e->getMessage());
        }
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
