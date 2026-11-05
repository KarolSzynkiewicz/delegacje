<?php

namespace Tests\Unit;

use App\Models\Accommodation;
use App\Models\AccommodationLease;
use App\Models\Document;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Project;
use App\Models\Rotation;
use App\Models\Vehicle;
use App\Services\DeadlineRadarService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeadlineRadarServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_build_merges_lanes_clusters_same_day_and_counts_kpis(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-14'));

        $employee = Employee::factory()->create();
        $docType = Document::factory()->create(['name' => 'Pasport']);

        EmployeeDocument::factory()->create([
            'employee_id' => $employee->id,
            'document_id' => $docType->id,
            'kind' => 'okresowy',
            'valid_to' => '2026-10-20',
        ]);
        EmployeeDocument::factory()->create([
            'employee_id' => Employee::factory()->create()->id,
            'document_id' => $docType->id,
            'kind' => 'okresowy',
            'valid_to' => '2026-10-20',
        ]);

        $house = Accommodation::factory()->create(['name' => 'Dom Test']);
        AccommodationLease::create([
            'accommodation_id' => $house->id,
            'type' => 'wynajmowany',
            'start_date' => '2026-01-01',
            'end_date' => '2026-10-25',
        ]);

        Project::factory()->create([
            'name' => 'Projekt Radar',
            'end_date' => '2026-10-18',
        ]);

        Rotation::factory()->create([
            'employee_id' => $employee->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-16',
        ]);

        Vehicle::factory()->create([
            'registration_number' => 'OC-SOON',
            'insurance_valid_to' => '2026-10-17',
            'ac_wazne_do' => '2026-10-17',
            'inspection_valid_to' => '2026-11-30',
        ]);
        Vehicle::factory()->create([
            'registration_number' => 'INSP-OVERDUE',
            'inspection_valid_to' => '2026-10-01',
        ]);

        $radar = app(DeadlineRadarService::class)->build(Carbon::parse('2026-10-14'));

        $this->assertSame(5, count($radar['lanes']));
        $this->assertSame(['projects', 'leases', 'rotations', 'vehicles', 'documents'], collect($radar['lanes'])->pluck('key')->all());

        $docCluster = collect($radar['clusters'])->firstWhere('key', 'documents|2026-10-20');
        $this->assertNotNull($docCluster);
        $this->assertSame(2, $docCluster['count']);
        $this->assertTrue($docCluster['show_count']);
        $this->assertSame('2', $docCluster['count_label']);

        $vehicleItems = collect($radar['items'])->where('lane', 'vehicles');
        $this->assertTrue($vehicleItems->contains(fn ($item) => $item['badge'] === 'OC' && $item['title'] === 'OC-SOON'));
        $this->assertTrue($vehicleItems->contains(fn ($item) => $item['badge'] === 'AC' && $item['title'] === 'OC-SOON'));
        $this->assertTrue($vehicleItems->contains(fn ($item) => $item['badge'] === 'Przegląd' && $item['title'] === 'INSP-OVERDUE' && $item['overdue']));

        $this->assertGreaterThanOrEqual(1, $radar['kpis']['overdue']);
        $this->assertGreaterThanOrEqual(1, $radar['kpis']['within_7']);
        $this->assertSame($radar['kpis']['monitored'], count($radar['items']));

        $overdueVehicleClusters = collect($radar['clusters'])
            ->where('lane', 'vehicles')
            ->where('overdue', true)
            ->values();
        $this->assertCount(1, $overdueVehicleClusters);
        $this->assertSame('vehicles|overdue', $overdueVehicleClusters[0]['key']);
        $this->assertSame('Zaległe', $overdueVehicleClusters[0]['due_label']);
    }

    public function test_anchor_is_reviewed_week_monday_not_wall_clock_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-04')); // Wednesday

        EmployeeDocument::factory()->create([
            'kind' => 'okresowy',
            'valid_to' => '2026-11-05', // Thu of current week
        ]);
        EmployeeDocument::factory()->create([
            'kind' => 'okresowy',
            'valid_to' => '2026-11-12', // next week
        ]);

        $currentWeek = app(DeadlineRadarService::class)->build(Carbon::parse('2026-11-02')); // Mon
        $nextWeek = app(DeadlineRadarService::class)->build(Carbon::parse('2026-11-09')); // next Mon

        $this->assertSame('2026-11-02', $currentWeek['today']);
        $this->assertSame('pon 02.11', $currentWeek['anchor_label']);
        $this->assertSame('2026-11-09', $nextWeek['today']);
        $this->assertSame('pon 09.11', $nextWeek['anchor_label']);

        $currentIds = collect($currentWeek['items'])->pluck('due_on')->all();
        $nextIds = collect($nextWeek['items'])->pluck('due_on')->all();

        $this->assertContains('2026-11-05', $currentIds);
        $this->assertContains('2026-11-12', $currentIds);
        // From next Monday, 05.11 is overdue (lookback still keeps it), 12.11 is +3
        $this->assertContains('2026-11-12', $nextIds);
        $nextItem = collect($nextWeek['items'])->firstWhere('due_on', '2026-11-12');
        $this->assertSame(3, $nextItem['days']);
    }

    public function test_build_uses_a_fixed_small_query_budget(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-14'));

        EmployeeDocument::factory()->count(3)->create([
            'kind' => 'okresowy',
            'valid_to' => '2026-10-20',
        ]);
        Vehicle::factory()->count(2)->create([
            'insurance_valid_to' => '2026-10-18',
            'ac_wazne_do' => '2026-10-19',
            'inspection_valid_to' => '2026-10-01',
        ]);
        Rotation::factory()->create([
            'end_date' => '2026-10-16',
            'start_date' => '2026-09-01',
        ]);
        Project::factory()->create(['end_date' => '2026-10-22']);

        DB::flushQueryLog();
        DB::enableQueryLog();
        app(DeadlineRadarService::class)->build(Carbon::parse('2026-10-14'));
        $queries = collect(DB::getQueryLog());

        // documents(+employees+document types) + leases(+acc) + projects + rotations(+employees) + 1 vehicles
        $this->assertLessThanOrEqual(9, $queries->count(), $queries->pluck('query')->implode("\n"));
        $this->assertSame(
            1,
            $queries->filter(fn (array $q) => str_contains($q['query'], 'from `vehicles`'))->count()
        );
    }
}
