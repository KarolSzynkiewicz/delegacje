<?php

namespace Tests\Unit;

use App\Models\Vehicle;
use App\Services\ExpiringDocumentsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpiringDocumentsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_vehicle_tile_counts_oc_ac_and_inspection_due_this_month_or_already_overdue(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-14'));

        Vehicle::factory()->create([
            'registration_number' => 'OC-MONTH',
            'insurance_valid_to' => '2026-10-20',
        ]);
        Vehicle::factory()->create([
            'registration_number' => 'AC-MONTH',
            'ac_wazne_do' => '2026-10-31',
        ]);
        Vehicle::factory()->create([
            'registration_number' => 'INSP-OVERDUE',
            'inspection_valid_to' => '2026-09-27',
        ]);
        Vehicle::factory()->create([
            'registration_number' => 'ALL-THREE',
            'insurance_valid_to' => '2026-03-28',
            'ac_wazne_do' => '2026-03-28',
            'inspection_valid_to' => '2026-03-28',
        ]);
        Vehicle::factory()->create([
            'registration_number' => 'LATER',
            'insurance_valid_to' => '2026-11-01',
            'ac_wazne_do' => '2027-03-20',
            'inspection_valid_to' => '2028-08-22',
        ]);
        Vehicle::factory()->create([
            'registration_number' => 'RETIRED',
            'insurance_valid_to' => '2026-09-28',
            'ac_wazne_do' => '2026-09-28',
            'inspection_valid_to' => '2026-09-28',
            'retired_at' => '2026-09-27 22:39:03',
        ]);

        $items = app(ExpiringDocumentsService::class)->getExpiringThisMonth();

        $this->assertSame(['ALL-THREE', 'OC-MONTH'], $items['vehicle_insurance']->pluck('registration_number')->all());
        $this->assertSame(['ALL-THREE', 'AC-MONTH'], $items['vehicle_ac']->pluck('registration_number')->all());
        $this->assertSame(['ALL-THREE', 'INSP-OVERDUE'], $items['vehicle_inspections']->pluck('registration_number')->all());
    }
}
