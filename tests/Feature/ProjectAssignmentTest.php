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
        $this->user = User::factory()->create();
    }



    public function test_can_create_project_assignment()
    {
        $employee = Employee::factory()->create();
        $project = Project::factory()->create();
        $role = Role::factory()->create();
        
        // Attach role to employee
        $employee->roles()->attach($role);
        
        // Create rotation for employee (required for assignment) - must cover entire assignment period
        Rotation::create([
            'employee_id' => $employee->id,
            'start_date' => now()->subYear()->format('Y-m-d'),
            'end_date' => now()->addYears(11)->format('Y-m-d'), // Extended to cover default end_date
        ]);
        
        // Create project demand (required for assignment)
        ProjectDemand::create([
            'project_id' => $project->id,
            'role_id' => $role->id,
            'required_count' => 1,
            'date_from' => now()->subYear()->format('Y-m-d'),
            'date_to' => now()->addYears(11)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->user)
            ->from(route('projects.assignments.create', $project))
            ->post(route('projects.assignments.store', $project), [
                'employee_id' => $employee->id,
                'role_id' => $role->id,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addYear()->format('Y-m-d'), // Explicit end_date
                'status' => 'active',
            ]);

        // Check if there are validation errors
        if ($response->getSession()->has('errors')) {
            $errors = $response->getSession()->get('errors');
            $this->fail('Validation failed: ' . json_encode($errors->all()));
        }
        
        $response->assertRedirect(route('projects.assignments.index', $project));
        $this->assertDatabaseHas('project_assignments', [
            'project_id' => $project->id,
            'employee_id' => $employee->id,
        ]);
    }

    public function test_employee_availability_validation()
    {
        $employee = Employee::factory()->create();
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();
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
            'date_from' => '2024-12-01',
            'date_to' => '2025-12-31',
        ]);
        
        ProjectDemand::create([
            'project_id' => $project2->id,
            'role_id' => $role->id,
            'required_count' => 1,
            'date_from' => '2024-12-01',
            'date_to' => '2025-12-31',
        ]);

        // Create first assignment
        ProjectAssignment::create([
            'project_id' => $project1->id,
            'employee_id' => $employee->id,
            'role_id' => $role->id,
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-31',
            'status' => 'active',
        ]);

        // Try to create overlapping assignment
        $response = $this->actingAs($this->user)->post(route('projects.assignments.store', $project2), [
            'employee_id' => $employee->id,
            'role_id' => $role->id,
            'start_date' => '2025-01-15',
            'end_date' => '2025-02-15',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('employee_id');
    }
}
