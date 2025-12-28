<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Role;
use App\Models\ProjectAssignment;
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

        $response = $this->actingAs($this->user)->post(route('projects.assignments.store', $project), [
            'employee_id' => $employee->id,
            'role_id' => $role->id,
            'start_date' => now()->format('Y-m-d'),
            'status' => 'active',
        ]);

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
