<?php

namespace Tests\Feature;

use App\Livewire\EmployeesTable;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeesTableHireFilterTest extends TestCase
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

    public function test_hire_period_filter_keeps_recent_hires_only(): void
    {
        $recent = Employee::factory()->create([
            'first_name' => 'Nowo',
            'last_name' => 'Zatrudniony',
            'hired_at' => now()->subDays(2),
        ]);
        $old = Employee::factory()->create([
            'first_name' => 'Dawno',
            'last_name' => 'ZatrudnionyStary',
            'hired_at' => now()->subDays(40),
        ]);

        Livewire::test(EmployeesTable::class)
            ->assertSee('Nowo Zatrudniony')
            ->assertSee('Dawno ZatrudnionyStary')
            ->set('hirePeriod', '7d')
            ->assertSee('Nowo Zatrudniony')
            ->assertDontSee('Dawno ZatrudnionyStary');

        $this->assertNotNull($recent->hired_at);
        $this->assertNotNull($old->hired_at);
    }

    public function test_index_shows_hire_date_column(): void
    {
        $employee = Employee::factory()->create([
            'first_name' => 'Jan',
            'last_name' => 'Datowy',
            'hired_at' => now()->subDays(3)->startOfDay(),
        ]);

        Livewire::test(EmployeesTable::class)
            ->assertSee('Zatrudniony')
            ->assertSee($employee->hired_at->format('Y-m-d'));
    }
}
