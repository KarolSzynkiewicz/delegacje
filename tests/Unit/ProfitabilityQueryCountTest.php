<?php

namespace Tests\Unit;

use App\Enums\ProjectType;
use App\Models\Accommodation;
use App\Models\AccommodationAssignment;
use App\Models\AccommodationLease;
use App\Models\Employee;
use App\Models\EmployeeRate;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\Role;
use App\Models\TimeLog;
use App\Services\ProfitabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProfitabilityQueryCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_trend_query_count_does_not_scale_with_months(): void
    {
        $month = Carbon::now()->startOfMonth();
        $role = Role::factory()->create();

        foreach (range(1, 4) as $i) {
            $employee = Employee::factory()->create();
            EmployeeRate::query()->create([
                'employee_id' => $employee->id,
                'start_date' => $month->copy()->subYear()->toDateString(),
                'amount' => 25,
                'currency' => 'EUR',
            ]);

            $project = Project::factory()->create([
                'type' => ProjectType::HOURLY,
                'hourly_rate' => 40,
                'currency' => 'EUR',
                'status' => 'active',
                'start_date' => $month->copy()->subMonths(11)->startOfMonth(),
                'end_date' => $month->copy()->endOfMonth(),
            ]);

            $assignment = ProjectAssignment::factory()->create([
                'project_id' => $project->id,
                'employee_id' => $employee->id,
                'role_id' => $role->id,
                'start_date' => $month->copy()->subMonths(11)->startOfMonth(),
                'end_date' => $month->copy()->endOfMonth(),
            ]);

            foreach ([0, 5, 11] as $monthsAgo) {
                $day = $month->copy()->subMonths($monthsAgo)->startOfMonth()->addDays(2);
                TimeLog::factory()->create([
                    'project_assignment_id' => $assignment->id,
                    'start_time' => $day->copy()->setTime(8, 0),
                    'end_time' => $day->copy()->setTime(16, 0),
                    'hours_worked' => 8,
                ]);
            }
        }

        $service = app(ProfitabilityService::class);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $trend = $service->getMonthlyTrend($month, 12);

        $queryCount = count(DB::getQueryLog());

        $this->assertCount(12, $trend['labels']);
        $this->assertNotEmpty($trend['summaries']);
        $this->assertArrayHasKey($month->copy()->subMonth()->format('Y-m'), $trend['summaries']);
        $this->assertLessThan(
            30,
            $queryCount,
            "Expected one range load for 12-month trend, got {$queryCount} queries"
        );
    }

    public function test_top_accommodation_costs_do_not_query_per_lease(): void
    {
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        foreach (range(1, 6) as $i) {
            $accommodation = Accommodation::factory()->create();
            $lease = AccommodationLease::query()->create([
                'accommodation_id' => $accommodation->id,
                'type' => 'wynajmowany',
                'start_date' => $monthStart->copy()->subMonth()->toDateString(),
                'end_date' => $monthEnd->copy()->addMonth()->toDateString(),
                'monthly_rent' => 300 + $i,
                'currency' => 'EUR',
            ]);
            AccommodationAssignment::factory()->create([
                'accommodation_id' => $accommodation->id,
                'start_date' => $monthStart,
                'end_date' => $monthEnd,
            ]);
            $this->assertNotNull($lease->id);
        }

        $service = app(ProfitabilityService::class);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $rows = $service->getTopAccommodationCostsForMonth($monthStart, $monthEnd, 10);

        $this->assertCount(6, $rows);
        $this->assertLessThan(
            8,
            count(DB::getQueryLog()),
            'Occupancy nights should use eager-loaded assignments, not one query per accommodation'
        );
    }
}
