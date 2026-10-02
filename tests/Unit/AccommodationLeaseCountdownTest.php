<?php

namespace Tests\Unit;

use App\Models\Accommodation;
use App\Models\AccommodationLease;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccommodationLeaseCountdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_countdown_stops_at_the_gap_before_the_next_lease(): void
    {
        $house = Accommodation::factory()->create();
        $this->lease($house, '2026-09-01', '2026-10-07');
        $this->lease($house, '2026-10-09', '2026-11-06');

        $this->assertSame('Koniec najmu: 2 dni', $house->leaseCountdownCaption('2026-10-05'));
        $this->assertSame('Ostatni dzień najmu', $house->leaseCountdownCaption('2026-10-07'));
        $this->assertSame('Przerwa w najmie', $house->leaseCountdownCaption('2026-10-08'));
    }

    public function test_countdown_runs_to_the_later_lease_when_they_touch(): void
    {
        $house = Accommodation::factory()->create();
        $this->lease($house, '2026-09-01', '2026-10-07');
        $this->lease($house, '2026-10-08', '2026-11-06');

        $this->assertSame('Koniec najmu: 32 dni', $house->leaseCountdownCaption('2026-10-05'));
    }

    public function test_open_lease_that_continues_the_current_one_has_no_end_date(): void
    {
        $house = Accommodation::factory()->create();
        $this->lease($house, '2026-09-01', '2026-10-07');
        $this->lease($house, '2026-10-08', null);

        $this->assertSame('Wynajem — brak daty końca', $house->leaseCountdownCaption('2026-10-05'));
    }

    private function lease(Accommodation $house, string $start, ?string $end): void
    {
        AccommodationLease::query()->create([
            'accommodation_id' => $house->id,
            'type' => 'wynajmowany',
            'start_date' => $start,
            'end_date' => $end,
            'monthly_rent' => 400,
            'currency' => 'EUR',
        ]);
    }
}
