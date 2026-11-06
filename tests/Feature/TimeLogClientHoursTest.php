<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\Role;
use App\Models\TimeLog;
use App\Models\User;
use App\Services\TimeLogService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeLogClientHoursTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_seed_copies_internal_hours_into_null_client_hours(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-10'));

        [$assignment, $log] = $this->makeAssignmentWithLog(8.0, null);

        $seeded = app(TimeLogService::class)->seedClientHoursFromInternal('2026-11', [$assignment->project_id]);

        $this->assertSame(1, $seeded);
        $this->assertEquals(8.0, (float) $log->fresh()->client_hours);
    }

    public function test_seed_does_not_overwrite_existing_client_hours(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-10'));

        [$assignment, $log] = $this->makeAssignmentWithLog(8.0, 6.5);

        app(TimeLogService::class)->seedClientHoursFromInternal('2026-11', [$assignment->project_id]);

        $this->assertEquals(6.5, (float) $log->fresh()->client_hours);
        $this->assertEquals(8.0, (float) $log->fresh()->hours_worked);
    }

    public function test_bulk_update_client_layer_overwrites_only_client_hours(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-10'));

        [$assignment, $log] = $this->makeAssignmentWithLog(8.0, 8.0);
        $admin = User::factory()->create();
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $admin->assignRole($adminRole);
        }

        $response = $this->actingAs($admin)->post(route('time-logs.bulk-update'), [
            'layer' => 'client',
            'entries' => [
                $assignment->id => [
                    '2026-11-05' => '7:00',
                ],
            ],
        ]);

        $response->assertRedirect();
        $log->refresh();
        $this->assertEquals(8.0, (float) $log->hours_worked);
        $this->assertEquals(7.0, (float) $log->client_hours);
        $this->assertTrue($log->hasClientMismatch());
    }

    public function test_discrepancies_list_and_settled_hours_bulk_update(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-10'));

        [$assignment, $log] = $this->makeAssignmentWithLog(8.0, 6.0);
        $matched = $this->makeAssignmentWithLog(8.0, 8.0)[1];

        $rows = app(TimeLogService::class)->getHourDiscrepancies('2026-11', [$assignment->project_id]);
        $this->assertCount(1, $rows);
        $this->assertSame($log->id, $rows[0]['time_log_id']);
        $this->assertEquals(8.0, $rows[0]['hours_worked']);
        $this->assertEquals(6.0, $rows[0]['client_hours']);
        $this->assertNull($rows[0]['settled_hours']);

        $admin = User::factory()->create();
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $admin->assignRole($adminRole);
        }

        $this->actingAs($admin)->post(route('time-logs.bulk-update'), [
            'layer' => 'settled',
            'entries' => [
                $assignment->id => [
                    '2026-11-05' => '7:00',
                ],
            ],
        ])->assertRedirect();

        $log->refresh();
        $matched->refresh();
        $this->assertEquals(7.0, (float) $log->settled_hours);
        $this->assertEquals(8.0, (float) $log->hours_worked);
        $this->assertEquals(6.0, (float) $log->client_hours);
        $this->assertNull($matched->settled_hours);
    }

    /**
     * @return array{0: ProjectAssignment, 1: TimeLog}
     */
    private function makeAssignmentWithLog(float $hoursWorked, ?float $clientHours): array
    {
        $employee = Employee::factory()->create();
        $project = Project::factory()->create();
        $role = Role::query()->first() ?? Role::factory()->create();

        $assignment = ProjectAssignment::factory()->create([
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
        ]);

        $log = TimeLog::create([
            'project_assignment_id' => $assignment->id,
            'start_time' => Carbon::parse('2026-11-05 08:00:00'),
            'end_time' => Carbon::parse('2026-11-05 08:00:00')->addHours($hoursWorked),
            'hours_worked' => $hoursWorked,
            'client_hours' => $clientHours,
        ]);

        return [$assignment, $log];
    }
}
