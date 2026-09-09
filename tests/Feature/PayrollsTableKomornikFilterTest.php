<?php

namespace Tests\Feature;

use App\Enums\PayrollStatus;
use App\Livewire\PayrollsTable;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PayrollsTableKomornikFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);
        $user = User::factory()->create();
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $user->assignRole($adminRole);
        }
        $this->actingAs($user);
    }

    public function test_komornik_filter_keeps_garnished_employees_only(): void
    {
        $with = Employee::factory()->create([
            'first_name' => 'Z',
            'last_name' => 'Komornikiem',
            'has_komornik' => true,
        ]);
        $without = Employee::factory()->create([
            'first_name' => 'Bez',
            'last_name' => 'Komornika',
            'has_komornik' => false,
        ]);

        $this->payrollFor($with);
        $this->payrollFor($without);

        Livewire::test(PayrollsTable::class)
            ->assertSee('Z Komornikiem')
            ->assertSee('Bez Komornika')
            ->set('komornikFilter', 'yes')
            ->assertSee('Z Komornikiem')
            ->assertDontSee('Bez Komornika')
            ->set('komornikFilter', 'no')
            ->assertSee('Bez Komornika')
            ->assertDontSee('Z Komornikiem');
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
