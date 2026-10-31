<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeRate;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\ProjectDemand;
use App\Models\Role;
use App\Models\User;
use App\Services\WeeklyOverviewService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeeklyOverviewAssignedRatesTest extends TestCase
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

    public function test_assigned_rows_include_hourly_rate_and_page_shows_average(): void
    {
        $weekStart = Carbon::parse('2026-10-19')->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();
        $project = Project::factory()->create();
        $role = Role::factory()->create(['name' => 'Monter']);

        ProjectDemand::factory()->create([
            'project_id' => $project->id,
            'role_id' => $role->id,
            'required_count' => 2,
            'start_date' => $weekStart,
            'end_date' => $weekEnd,
        ]);

        $fast = Employee::factory()->create(['first_name' => 'Ada', 'last_name' => 'Szybka']);
        $slow = Employee::factory()->create(['first_name' => 'Ola', 'last_name' => 'Wolna']);
        $fast->roles()->sync([$role->id]);
        $slow->roles()->sync([$role->id]);

        foreach ([$fast, $slow] as $employee) {
            ProjectAssignment::factory()->create([
                'project_id' => $project->id,
                'employee_id' => $employee->id,
                'role_id' => $role->id,
                'start_date' => $weekStart,
                'end_date' => $weekEnd,
            ]);
        }

        EmployeeRate::query()->create([
            'employee_id' => $fast->id,
            'start_date' => $weekStart->copy()->subMonth(),
            'end_date' => null,
            'amount' => 14,
            'currency' => 'EUR',
            'status' => 'active',
        ]);
        EmployeeRate::query()->create([
            'employee_id' => $slow->id,
            'start_date' => $weekStart->copy()->subMonth(),
            'end_date' => null,
            'amount' => 12,
            'currency' => 'EUR',
            'status' => 'active',
        ]);

        $weeks = app(WeeklyOverviewService::class)->getWeeks($weekStart);
        $projects = app(WeeklyOverviewService::class)->getProjectsWithWeeklyData($weeks);
        $weekData = collect($projects)->firstWhere(fn ($row) => $row['project']->id === $project->id)['weeks_data'][0];
        $byId = $weekData['assigned_employees']->keyBy(fn ($row) => $row['employee']->id);

        $this->assertSame(14.0, $byId[$fast->id]['hourly_rate']['amount']);
        $this->assertSame('14e', $byId[$fast->id]['hourly_rate']['label']);
        $this->assertSame('12e', $byId[$slow->id]['hourly_rate']['label']);

        $this->get(route('weekly-overview.index', [
            'start_date' => $weekStart->format('Y-m-d'),
            'project_id' => $project->id,
        ]))
            ->assertOk()
            ->assertSee('śr. 13e', false)
            ->assertSee('>14e<', false)
            ->assertSee('>12e<', false)
            ->assertSee('Stawka', false);
    }

    public function test_compact_rate_label_formats_common_currencies(): void
    {
        $this->assertSame('13e', EmployeeRate::formatCompactLabel(13, 'EUR'));
        $this->assertSame('12,5e', EmployeeRate::formatCompactLabel(12.5, 'EUR'));
        $this->assertSame('55zł', EmployeeRate::formatCompactLabel(55, 'PLN'));
        $this->assertSame('18$', EmployeeRate::formatCompactLabel(18, 'USD'));
    }

    public function test_average_badge_uses_only_eur_rates(): void
    {
        $weekStart = Carbon::parse('2026-10-19')->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();
        $project = Project::factory()->create();
        $role = Role::factory()->create();

        ProjectDemand::factory()->create([
            'project_id' => $project->id,
            'role_id' => $role->id,
            'required_count' => 2,
            'start_date' => $weekStart,
            'end_date' => $weekEnd,
        ]);

        $eurEmployee = Employee::factory()->create();
        $plnEmployee = Employee::factory()->create();
        $eurEmployee->roles()->sync([$role->id]);
        $plnEmployee->roles()->sync([$role->id]);

        foreach ([$eurEmployee, $plnEmployee] as $employee) {
            ProjectAssignment::factory()->create([
                'project_id' => $project->id,
                'employee_id' => $employee->id,
                'role_id' => $role->id,
                'start_date' => $weekStart,
                'end_date' => $weekEnd,
            ]);
        }

        EmployeeRate::query()->create([
            'employee_id' => $eurEmployee->id,
            'start_date' => $weekStart->copy()->subMonth(),
            'end_date' => null,
            'amount' => 25,
            'currency' => 'EUR',
            'status' => 'active',
        ]);
        EmployeeRate::query()->create([
            'employee_id' => $plnEmployee->id,
            'start_date' => $weekStart->copy()->subMonth(),
            'end_date' => null,
            'amount' => 15,
            'currency' => 'PLN',
            'status' => 'active',
        ]);

        $this->get(route('weekly-overview.index', [
            'start_date' => $weekStart->format('Y-m-d'),
            'project_id' => $project->id,
        ]))
            ->assertOk()
            ->assertSee('śr. 25e', false)
            ->assertDontSee('śr. 20e', false)
            ->assertSee('>25e<', false)
            ->assertSee('>15zł<', false);
    }
}
