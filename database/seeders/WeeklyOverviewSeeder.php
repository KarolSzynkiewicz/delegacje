<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Accommodation;
use App\Models\Vehicle;
use App\Models\ProjectDemand;
use App\Models\ProjectAssignment;
use App\Models\AccommodationAssignment;
use App\Models\VehicleAssignment;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class WeeklyOverviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get current week start
        $currentWeekStart = Carbon::now()->startOfWeek();
        
        // Get projects
        $projects = Project::all();
        if ($projects->isEmpty()) {
            $this->command->warn('No projects found. Please run ProjectSeeder first.');
            return;
        }
        
        // Get employees
        $employees = Employee::all();
        if ($employees->isEmpty()) {
            $this->command->warn('No employees found. Please run EmployeeSeeder first.');
            return;
        }
        
        // Get roles
        $roles = Role::all();
        if ($roles->isEmpty()) {
            $this->command->warn('No roles found. Please run RoleSeeder first.');
            return;
        }
        
        // Get accommodations
        $accommodations = Accommodation::all();
        if ($accommodations->isEmpty()) {
            $this->command->warn('No accommodations found. Please run AccommodationSeeder first.');
            return;
        }
        
        // Get vehicles
        $vehicles = Vehicle::all();
        if ($vehicles->isEmpty()) {
            $this->command->warn('No vehicles found. Please run VehicleSeeder first.');
            return;
        }
        
        // For each project, create demands and assignments for 3 weeks
        foreach ($projects->take(2) as $projectIndex => $project) {
            $weekStart = $currentWeekStart->copy()->addWeeks($projectIndex);
            
            for ($week = 0; $week < 3; $week++) {
                $weekStartDate = $weekStart->copy()->addWeeks($week);
                $weekEndDate = $weekStartDate->copy()->endOfWeek();
                
                // Create demands for this week
                $demandRoles = $roles->random(min(3, $roles->count()));
                foreach ($demandRoles as $role) {
                    $requiredCount = rand(2, 5);
                    
                    ProjectDemand::updateOrCreate(
                        [
                            'project_id' => $project->id,
                            'role_id' => $role->id,
                            'date_from' => $weekStartDate,
                            'date_to' => $weekEndDate,
                        ],
                        [
                            'required_count' => $requiredCount,
                            'notes' => null,
                        ]
                    );
                }
                
                // Create assignments for this week
                $assignedEmployees = $employees->random(min(5, $employees->count()));
                $assignedRoles = $roles->random(min(3, $roles->count()));
                
                foreach ($assignedEmployees as $index => $employee) {
                    $role = $assignedRoles[$index % $assignedRoles->count()];
                    
                    ProjectAssignment::updateOrCreate(
                        [
                            'project_id' => $project->id,
                            'employee_id' => $employee->id,
                            'start_date' => $weekStartDate,
                            'end_date' => $weekEndDate,
                        ],
                        [
                            'role_id' => $role->id,
                            'status' => 'active',
                            'notes' => null,
                        ]
                    );
                    
                    // Assign accommodation to employee for this week
                    if ($accommodations->isNotEmpty()) {
                        $accommodation = $accommodations->random();
                        
                        AccommodationAssignment::updateOrCreate(
                            [
                                'employee_id' => $employee->id,
                                'accommodation_id' => $accommodation->id,
                                'start_date' => $weekStartDate,
                                'end_date' => $weekEndDate,
                            ],
                            [
                                'notes' => null,
                            ]
                        );
                    }
                    
                    // Assign vehicle to employee for this week
                    if ($vehicles->isNotEmpty()) {
                        $vehicle = $vehicles->random();
                        
                        VehicleAssignment::updateOrCreate(
                            [
                                'employee_id' => $employee->id,
                                'vehicle_id' => $vehicle->id,
                                'start_date' => $weekStartDate,
                                'end_date' => $weekEndDate,
                            ],
                            [
                                'notes' => null,
                            ]
                        );
                    }
                }
            }
        }
        
        $this->command->info('Weekly overview data seeded successfully!');
    }
}

