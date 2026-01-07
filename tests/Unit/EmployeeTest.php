<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Role;
use App\Models\ProjectAssignment;
use App\Models\Rotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_full_name_attribute()
    {
        $employee = new Employee([
            'first_name' => 'Jan',
            'last_name' => 'Kowalski'
        ]);

        $this->assertEquals('Jan Kowalski', $employee->full_name);
    }

    public function test_employee_availability_logic()
    {
        $employee = Employee::factory()->create();
        $project = Project::factory()->create();
        $role = Role::factory()->create();
        
        // Create rotation for employee (required for availability check)
        Rotation::create([
            'employee_id' => $employee->id,
            'start_date' => '2024-12-01',
            'end_date' => '2025-12-31',
        ]);

        // No assignments - should be available
        $this->assertTrue($employee->isAvailableInDateRange('2025-01-01', '2025-01-31'));

        // Create an active assignment
        ProjectAssignment::create([
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => '2025-01-10',
            'end_date' => '2025-01-20',
            'status' => 'active'
        ]);

        // Overlapping range
        $this->assertFalse($employee->isAvailableInDateRange('2025-01-01', '2025-01-31'));
        $this->assertFalse($employee->isAvailableInDateRange('2025-01-15', '2025-01-25'));
        
        // Non-overlapping range
        $this->assertTrue($employee->isAvailableInDateRange('2025-01-21', '2025-01-31'));
        $this->assertTrue($employee->isAvailableInDateRange('2025-01-01', '2025-01-09'));
    }
}
