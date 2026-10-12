<?php

namespace Tests\Unit;

use App\Enums\PayrollStatus;
use App\Models\Adjustment;
use App\Models\Advance;
use App\Models\EmployeeRate;
use App\Models\Payroll;
use App\Models\ProjectAssignment;
use App\Models\TimeLog;
use App\Services\GeneratePayrollForEmployee;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PayrollRecalculateManyTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_recalculate_matches_per_day_rates_and_uses_one_read_per_table(): void
    {
        $assignment = ProjectAssignment::factory()->create();
        $employee = $assignment->employee;

        EmployeeRate::query()->create([
            'employee_id' => $employee->id,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-10',
            'amount' => 80,
            'currency' => 'PLN',
        ]);
        EmployeeRate::query()->create([
            'employee_id' => $employee->id,
            'start_date' => '2026-03-05',
            'end_date' => null,
            'amount' => 120,
            'currency' => 'PLN',
        ]);

        $this->logHours($assignment->id, '2026-03-02', 8);
        $this->logHours($assignment->id, '2026-03-06', 4);
        $this->logHours($assignment->id, '2026-02-20', 6);

        $payroll = Payroll::query()->create([
            'employee_id' => $employee->id,
            'period_start' => '2026-03-01',
            'period_end' => '2026-03-31',
            'hours_amount' => 0,
            'adjustments_amount' => 0,
            'total_amount' => 0,
            'currency' => 'PLN',
            'status' => PayrollStatus::DRAFT,
        ]);

        Adjustment::query()->create([
            'employee_id' => $employee->id,
            'payroll_id' => $payroll->id,
            'amount' => 30,
            'currency' => 'PLN',
            'type' => 'bonus',
            'date' => '2026-03-15',
        ]);
        Adjustment::query()->create([
            'employee_id' => $employee->id,
            'payroll_id' => $payroll->id,
            'amount' => 10,
            'currency' => 'PLN',
            'type' => 'penalty',
            'date' => '2026-03-16',
        ]);
        Advance::query()->create([
            'employee_id' => $employee->id,
            'payroll_id' => $payroll->id,
            'amount' => 20,
            'currency' => 'PLN',
            'date' => '2026-03-10',
            'is_interest_bearing' => true,
            'interest_rate' => 10,
        ]);

        $locked = Payroll::query()->create([
            'employee_id' => $employee->id,
            'period_start' => '2026-02-01',
            'period_end' => '2026-02-28',
            'hours_amount' => 1,
            'adjustments_amount' => 0,
            'total_amount' => 1,
            'currency' => 'EUR',
            'status' => PayrollStatus::PAID,
        ]);

        $service = app(GeneratePayrollForEmployee::class);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $result = $service->recalculateMany([$payroll, $locked]);
        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();

        $payroll->refresh();

        $this->assertSame(1, $result['recalculated']);
        $this->assertSame($locked->id, $result['errors'][0]['id']);
        $this->assertSame('1120.00', $payroll->hours_amount);
        $this->assertSame('-2.00', $payroll->adjustments_amount);
        $this->assertSame('1118.00', $payroll->total_amount);
        $this->assertSame('PLN', $payroll->currency);
        $this->assertSame(1, $queries->filter(fn (string $sql) => str_contains($sql, 'time_logs'))->count());
        $this->assertSame(1, $queries->filter(fn (string $sql) => str_contains($sql, 'employee_rates'))->count());
        $this->assertSame(1, $queries->filter(fn (string $sql) => str_contains($sql, 'from `adjustments`') || str_contains($sql, 'from "adjustments"'))->count());
        $this->assertSame(1, $queries->filter(fn (string $sql) => str_contains($sql, 'from `advances`') || str_contains($sql, 'from "advances"'))->count());

        $locked->refresh();
        $this->assertSame('1.00', $locked->hours_amount);
    }

    private function logHours(int $assignmentId, string $date, float $hours): void
    {
        $start = Carbon::parse($date)->setTime(8, 0);

        TimeLog::query()->create([
            'project_assignment_id' => $assignmentId,
            'start_time' => $start,
            'end_time' => $start->copy()->addHours($hours),
            'hours_worked' => $hours,
        ]);
    }
}
