<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeRateTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->user = User::factory()->create();
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $this->user->assignRole($adminRole);
        }
        $this->actingAs($this->user);
    }

    public function test_create_form_preselects_employee_from_query_string(): void
    {
        $employee = Employee::factory()->create();
        $other = Employee::factory()->create();

        $this->get(route('employee-rates.create', ['employee_id' => $employee->id]))
            ->assertOk()
            ->assertSee('value="'.$employee->id.'" selected', false)
            ->assertDontSee('value="'.$other->id.'" selected', false)
            ->assertSee(route('employees.show', ['employee' => $employee, 'tab' => 'employee-rates']), false);
    }

    public function test_storing_a_rate_returns_to_the_employee_card(): void
    {
        $employee = Employee::factory()->create();

        $this->from(route('employee-rates.create', ['employee_id' => $employee->id]))
            ->post(route('employee-rates.store'), [
                'employee_id' => $employee->id,
                'start_date' => now()->toDateString(),
                'amount' => 120,
                'currency' => 'PLN',
            ])
            ->assertRedirect(route('employees.show', [
                'employee' => $employee,
                'tab' => 'employee-rates',
            ]));

        $this->assertDatabaseHas('employee_rates', [
            'employee_id' => $employee->id,
            'amount' => 120,
            'currency' => 'PLN',
        ]);
    }
}
