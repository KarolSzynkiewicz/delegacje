<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Services\EmployeeRoleSeniorityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EmployeeRoleSeniorityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_level_writes_history_and_skips_unchanged(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $role = Role::factory()->create();
        $employee = Employee::factory()->create();
        $employee->roles()->sync([$role->id]);

        $service = app(EmployeeRoleSeniorityService::class);
        $service->setLevel($employee, $role->id, 2, 'start', $user);

        $this->assertSame(2, (int) $employee->roles()->first()->pivot->seniority);
        $this->assertDatabaseCount('employee_role_seniority_changes', 1);

        $service->setLevel($employee->fresh(), $role->id, 2, 'again', $user);
        $this->assertDatabaseCount('employee_role_seniority_changes', 1);

        $service->setLevel($employee->fresh(), $role->id, null, 'reset', $user);
        $this->assertNull($employee->fresh()->roles()->first()->pivot->seniority);
        $this->assertDatabaseCount('employee_role_seniority_changes', 2);
    }

    public function test_sync_preserves_seniority_when_not_submitted(): void
    {
        $role = Role::factory()->create();
        $other = Role::factory()->create();
        $employee = Employee::factory()->create();
        $employee->roles()->sync([$role->id => ['seniority' => 3]]);

        app(EmployeeRoleSeniorityService::class)->syncRoles($employee, [$role->id, $other->id]);

        $roles = $employee->fresh()->roles()->get()->keyBy('id');
        $this->assertSame(3, (int) $roles[$role->id]->pivot->seniority);
        $this->assertNull($roles[$other->id]->pivot->seniority);
    }

    public function test_cannot_set_seniority_for_missing_trade(): void
    {
        $role = Role::factory()->create();
        $employee = Employee::factory()->create();
        $employee->roles()->sync([]);

        $this->expectException(ValidationException::class);
        app(EmployeeRoleSeniorityService::class)->setLevel($employee, $role->id, 3);
    }
}
