<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Role;
use App\Models\ProjectAssignment;
use App\Models\ProjectDemand;
use App\Models\Rotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run seeders to set up roles
        // Note: Permissions are now generated dynamically from routes, no PermissionSeeder needed
        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);
        
        $this->user = User::factory()->create();
        
        // Assign administrator role to user for tests
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $this->user->assignRole($adminRole);
        }
    }



    public function test_can_create_project_assignment()
    {
        // Create base location
        $baseLocation = \App\Models\Location::factory()->create(['is_base' => true]);
        
        $employee = Employee::factory()->create();
        // Create project in BASE location (to avoid departure requirement)
        $project = Project::factory()->create(['location_id' => $baseLocation->id]);
        $role = Role::factory()->create();
        
        // Attach role to employee
        $employee->roles()->attach($role);
        
        $startDate = now();
        $endDate = now()->addMonths(3);
        
        // Create rotation for employee (required for assignment) - must cover entire assignment period
        Rotation::create([
            'employee_id' => $employee->id,
            'start_date' => $startDate->copy()->subMonth()->format('Y-m-d'),
            'end_date' => $endDate->copy()->addMonth()->format('Y-m-d'),
        ]);
        
        // Create project demand (required for assignment) - must cover assignment period
        ProjectDemand::create([
            'project_id' => $project->id,
            'role_id' => $role->id,
            'required_count' => 1,
            'start_date' => $startDate->copy()->subMonth()->format('Y-m-d'),
            'end_date' => $endDate->copy()->addMonth()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->user)
            ->from(route('project-assignments.create', ['project_id' => $project->id]))
            ->post(route('project-assignments.store'), [
                'project_id' => $project->id,
                'employee_id' => $employee->id,
                'role_id' => $role->id,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ]);

        // Check if there are validation errors
        if ($response->getSession()->has('errors')) {
            $errors = $response->getSession()->get('errors');
            $this->fail('Validation failed: ' . json_encode($errors->all()));
        }
        
        // Po utworzeniu przypisania, redirect jest do weekly-overview, więc sprawdzamy tylko że nie ma błędów
        $response->assertRedirect();
        $this->assertDatabaseHas('project_assignments', [
            'project_id' => $project->id,
            'employee_id' => $employee->id,
        ]);
    }

    public function test_employee_availability_validation()
    {
        // Create base location
        $baseLocation = \App\Models\Location::factory()->create(['is_base' => true]);
        
        $employee = Employee::factory()->create();
        // Both projects in BASE to avoid departure logic
        $project1 = Project::factory()->create(['location_id' => $baseLocation->id]);
        $project2 = Project::factory()->create(['location_id' => $baseLocation->id]);
        $role = Role::factory()->create();
        
        // Attach role to employee
        $employee->roles()->attach($role);
        
        // Create rotation for employee (required for assignment)
        Rotation::create([
            'employee_id' => $employee->id,
            'start_date' => '2024-12-01',
            'end_date' => '2025-12-31',
        ]);
        
        // Create project demands for both projects
        ProjectDemand::create([
            'project_id' => $project1->id,
            'role_id' => $role->id,
            'required_count' => 1,
            'start_date' => '2024-12-01',
            'end_date' => '2025-12-31',
        ]);
        
        ProjectDemand::create([
            'project_id' => $project2->id,
            'role_id' => $role->id,
            'required_count' => 1,
            'start_date' => '2024-12-01',
            'end_date' => '2025-12-31',
        ]);

        // Create first assignment
        ProjectAssignment::create([
            'project_id' => $project1->id,
            'employee_id' => $employee->id,
            'role_id' => $role->id,
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-31',
        ]);

        // Try to create overlapping assignment
        $response = $this->actingAs($this->user)->post(route('project-assignments.store'), [
            'project_id' => $project2->id,
            'employee_id' => $employee->id,
            'role_id' => $role->id,
            'start_date' => '2025-01-15',
            'end_date' => '2025-02-15',
        ]);

        $response->assertSessionHasErrors('employee_id');
    }
}
