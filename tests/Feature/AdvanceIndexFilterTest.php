<?php

namespace Tests\Feature;

use App\Models\Advance;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvanceIndexFilterTest extends TestCase
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

        $this->makeAdvance($jan);
        $this->makeAdvance($anna);

        $this->get(route('advances.index'))
            ->assertOk()
            ->assertSee('Jan Kowalski')
            ->assertSee('Anna Nowak');

        $this->get(route('advances.index', ['employee' => 'Kowal']))
            ->assertOk()
            ->assertSee('Jan Kowalski')
            ->assertDontSee('Anna Nowak');

        $this->get(route('advances.index', ['employee' => 'Anna']))
            ->assertOk()
            ->assertSee('Anna Nowak')
            ->assertDontSee('Jan Kowalski');
    }

    private function makeAdvance(Employee $employee): Advance
    {
        return Advance::query()->create([
            'employee_id' => $employee->id,
            'payroll_id' => null,
            'amount' => 100,
            'currency' => 'PLN',
            'date' => now()->toDateString(),
            'notes' => null,
        ]);
    }
}
