<?php

namespace Tests\Feature;

use App\Enums\PayrollStatus;
use App\Models\Advance;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvanceWithoutPayrollTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->user = User::factory()->create();
        $admin = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($admin) {
            $this->user->assignRole($admin);
        }
        $this->actingAs($this->user);
    }

    public function test_advance_can_be_created_without_a_payroll(): void
    {
        $employee = Employee::factory()->create();

        $this->post(route('advances.store'), [
            'employee_id' => $employee->id,
            'payroll_id' => '',
            'amount' => 500,
            'currency' => 'PLN',
            'date' => '2026-09-04',
            'notes' => 'Na paliwo',
        ])->assertRedirect(route('advances.index'));

        $advance = Advance::query()->first();
        $this->assertNotNull($advance);
        $this->assertSame($employee->id, $advance->employee_id);
        $this->assertNull($advance->payroll_id);
        $this->assertEquals('500.00', $advance->amount);
    }

    public function test_advance_can_be_linked_to_the_employees_payroll(): void
    {
        $employee = Employee::factory()->create();
        $payroll = $this->payrollFor($employee);

        $this->post(route('advances.store'), [
            'employee_id' => $employee->id,
            'payroll_id' => $payroll->id,
            'amount' => 200,
            'currency' => 'PLN',
            'date' => '2026-09-04',
        ])->assertRedirect(route('advances.index'));

        $advance = Advance::query()->first();
        $this->assertSame($payroll->id, $advance->payroll_id);
        $this->assertSame($employee->id, $advance->employee_id);
    }

    public function test_advance_cannot_use_another_employees_payroll(): void
    {
        $employee = Employee::factory()->create();
        $other = Employee::factory()->create();
        $payroll = $this->payrollFor($other);

        $this->post(route('advances.store'), [
            'employee_id' => $employee->id,
            'payroll_id' => $payroll->id,
            'amount' => 200,
            'currency' => 'PLN',
            'date' => '2026-09-04',
        ])->assertSessionHasErrors('payroll_id');

        $this->assertSame(0, Advance::query()->count());
    }

    public function test_unlinked_advance_can_get_a_payroll_later(): void
    {
        $employee = Employee::factory()->create();
        $payroll = $this->payrollFor($employee);
        $advance = Advance::query()->create([
            'employee_id' => $employee->id,
            'payroll_id' => null,
            'amount' => 150,
            'currency' => 'PLN',
            'date' => '2026-09-01',
        ]);

        $this->put(route('advances.update', $advance), [
            'employee_id' => $employee->id,
            'payroll_id' => $payroll->id,
            'amount' => 150,
            'currency' => 'PLN',
            'date' => '2026-09-01',
        ])->assertRedirect(route('advances.index'));

        $this->assertSame($payroll->id, $advance->fresh()->payroll_id);
    }

    public function test_create_form_does_not_require_payroll(): void
    {
        $this->get(route('advances.create'))
            ->assertOk()
            ->assertSee('Pracownik')
            ->assertSee('Payroll (opcjonalnie)')
            ->assertSee('przypisz później')
            ->assertDontSee('Payroll jest wymagany');
    }

    private function payrollFor(Employee $employee): Payroll
    {
        return Payroll::query()->create([
            'employee_id' => $employee->id,
            'period_start' => '2026-09-01',
            'period_end' => '2026-09-30',
            'hours_amount' => 0,
            'adjustments_amount' => 0,
            'total_amount' => 0,
            'currency' => 'PLN',
            'status' => PayrollStatus::DRAFT,
        ]);
    }
}
