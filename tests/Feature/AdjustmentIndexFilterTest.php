<?php

namespace Tests\Feature;

use App\Models\Adjustment;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdjustmentIndexFilterTest extends TestCase
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

    public function test_index_filters_by_employee_first_or_last_name(): void
    {
        $jan = Employee::factory()->create([
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
        ]);
        $anna = Employee::factory()->create([
            'first_name' => 'Anna',
            'last_name' => 'Nowak',
        ]);

        $this->makeAdjustment($jan, 'bonus');
        $this->makeAdjustment($anna, 'penalty');

        $this->get(route('adjustments.index'))
            ->assertOk()
            ->assertSee('Jan Kowalski')
            ->assertSee('Anna Nowak');

        $this->get(route('adjustments.index', ['employee' => 'Kowal']))
            ->assertOk()
            ->assertSee('Jan Kowalski')
            ->assertDontSee('Anna Nowak');

        $this->get(route('adjustments.index', ['employee' => 'Anna']))
            ->assertOk()
            ->assertSee('Anna Nowak')
            ->assertDontSee('Jan Kowalski');

        $this->get(route('adjustments.index', ['employee' => 'Jan Kowalski']))
            ->assertOk()
            ->assertSee('Jan Kowalski')
            ->assertDontSee('Anna Nowak');
    }

    private function makeAdjustment(Employee $employee, string $type): Adjustment
    {
        return Adjustment::query()->create([
            'employee_id' => $employee->id,
            'payroll_id' => null,
            'amount' => 100,
            'currency' => 'PLN',
            'type' => $type,
            'date' => now()->toDateString(),
            'notes' => null,
        ]);
    }
}
