<?php

namespace Tests\Feature;

use App\Models\Accommodation;
use App\Models\AccommodationAssignment;
use App\Models\AccommodationLease;
use App\Models\Employee;
use App\Services\AccommodationAssignmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AccommodationAssignmentCapacityTest extends TestCase
{
    use RefreshDatabase;

    protected AccommodationAssignmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-06-15');
        $this->service = app(AccommodationAssignmentService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_sequential_stays_do_not_block_a_spanning_assignment_when_peak_occupancy_is_below_capacity(): void
    {
        $house = Accommodation::factory()->create(['capacity' => 2]);

        AccommodationAssignment::factory()->create([
            'accommodation_id' => $house->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-10',
        ]);
        AccommodationAssignment::factory()->create([
            'accommodation_id' => $house->id,
            'start_date' => '2026-07-11',
            'end_date' => '2026-07-20',
        ]);

        $this->assertSame(1, $house->getPeakOccupancy('2026-07-01', '2026-07-20'));
        $this->assertSame(1, $house->getAvailableCapacity('2026-07-01', '2026-07-20'));
        $this->assertTrue($house->hasAvailableSpace('2026-07-01', '2026-07-20'));

        $newcomer = Employee::factory()->create();
        $assignment = $this->service->createAssignment(
            $newcomer,
            $house->fresh(),
            Carbon::parse('2026-07-01'),
            Carbon::parse('2026-07-20')
        );

        $this->assertDatabaseHas('accommodation_assignments', [
            'id' => $assignment->id,
            'employee_id' => $newcomer->id,
            'accommodation_id' => $house->id,
        ]);
    }

    public function test_concurrent_stays_at_capacity_block_save_with_the_full_day_named(): void
    {
        $house = Accommodation::factory()->create(['name' => 'Dom Testowy', 'capacity' => 1]);
        AccommodationAssignment::factory()->create([
            'accommodation_id' => $house->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-20',
        ]);

        $this->assertFalse($house->hasAvailableSpace('2026-07-05', '2026-07-08'));

        try {
            $this->service->createAssignment(
                Employee::factory()->create(),
                $house->fresh(),
                Carbon::parse('2026-07-05'),
                Carbon::parse('2026-07-08')
            );
            $this->fail('Expected capacity validation to fail.');
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first();
            $this->assertStringContainsString('Dom Testowy', $message);
            $this->assertStringContainsString('05.07.2026', $message);
        }
    }

    public function test_lease_is_resolved_for_assignment_dates_not_the_lease_active_today(): void
    {
        $house = Accommodation::factory()->create(['capacity' => 2]);

        AccommodationLease::query()->create([
            'accommodation_id' => $house->id,
            'type' => 'wynajmowany',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
        ]);
        AccommodationLease::query()->create([
            'accommodation_id' => $house->id,
            'type' => 'wynajmowany',
            'start_date' => '2026-04-01',
            'end_date' => '2026-12-31',
        ]);

        Carbon::setTestNow('2026-02-15');

        $assignment = $this->service->createAssignment(
            Employee::factory()->create(),
            $house->fresh(),
            Carbon::parse('2026-02-10'),
            Carbon::parse('2026-02-20')
        );

        $this->assertNotNull($assignment->id);
    }

    public function test_employee_cannot_have_two_houses_on_the_same_days(): void
    {
        $employee = Employee::factory()->create();
        $houseA = Accommodation::factory()->create(['name' => 'Dom A', 'capacity' => 4]);
        $houseB = Accommodation::factory()->create(['name' => 'Dom B', 'capacity' => 4]);

        $this->service->createAssignment(
            $employee,
            $houseA,
            Carbon::parse('2026-07-01'),
            Carbon::parse('2026-07-31')
        );

        try {
            $this->service->createAssignment(
                $employee,
                $houseB,
                Carbon::parse('2026-07-15'),
                Carbon::parse('2026-07-20')
            );
            $this->fail('Expected overlap validation to fail.');
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first();
            $this->assertStringContainsString('Dom A', $message);
        }
    }

    public function test_transfer_can_ignore_the_stay_that_will_be_shortened(): void
    {
        $employee = Employee::factory()->create();
        $current = Accommodation::factory()->create(['name' => 'SAINT MALO', 'capacity' => 4]);
        $next = Accommodation::factory()->create(['name' => 'Nowy dom', 'capacity' => 4]);

        $old = $this->service->createAssignment(
            $employee,
            $current,
            Carbon::parse('2026-09-21'),
            Carbon::parse('2026-10-03')
        );

        $this->service->assertCanAssign(
            $employee,
            $next,
            Carbon::parse('2026-10-03'),
            Carbon::parse('2026-10-20'),
            null,
            Carbon::parse('2026-10-03'),
            [$old->id]
        );

        $this->assertTrue(true);
    }
}
