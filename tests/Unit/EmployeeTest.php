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
}
