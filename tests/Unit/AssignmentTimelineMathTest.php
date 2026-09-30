<?php

namespace Tests\Unit;

use App\Support\AssignmentTimelineMath;
use Tests\TestCase;

class AssignmentTimelineMathTest extends TestCase
{
    public function test_peak_counts_the_worst_day_not_the_number_of_stays(): void
    {
        $sequential = AssignmentTimelineMath::peak([
            ['start' => '2026-07-01', 'end' => '2026-07-10'],
            ['start' => '2026-07-11', 'end' => '2026-07-20'],
        ], '2026-07-01', '2026-07-20', 2);

        $this->assertSame(1, $sequential['peak']);
        $this->assertNull($sequential['full_from']);

        $stacked = AssignmentTimelineMath::peak([
            ['start' => '2026-07-01', 'end' => '2026-07-20'],
            ['start' => '2026-07-05', 'end' => '2026-07-08'],
        ], '2026-07-01', '2026-07-20', 2);

        $this->assertSame(2, $stacked['peak']);
        $this->assertSame('2026-07-05', $stacked['full_from']);
    }

    public function test_min_free_is_the_worst_day_and_a_day_without_demand_is_zero(): void
    {
        $free = AssignmentTimelineMath::minFree(
            [['start' => '2026-07-01', 'end' => '2026-07-03', 'required' => 2]],
            [['start' => '2026-07-01', 'end' => '2026-07-03']],
            '2026-07-01',
            '2026-07-03',
        );
        $this->assertSame(1, $free);

        $gap = AssignmentTimelineMath::minFree(
            [['start' => '2026-07-01', 'end' => '2026-07-02', 'required' => 3]],
            [],
            '2026-07-01',
            '2026-07-03',
        );
        $this->assertSame(0, $gap);
    }

    public function test_gaps_stop_at_blocks_and_resize_stops_at_neighbors(): void
    {
        $gaps = AssignmentTimelineMath::gaps(
            [['start' => '2026-07-01', 'end' => '2026-07-30']],
            [['start' => '2026-07-10', 'end' => '2026-07-20']],
        );

        $this->assertSame([
            ['start' => '2026-07-01', 'end' => '2026-07-09'],
            ['start' => '2026-07-21', 'end' => '2026-07-30'],
        ], $gaps);

        $limits = AssignmentTimelineMath::resizeLimits(
            '2026-07-10',
            '2026-07-20',
            [
                ['start' => '2026-07-01', 'end' => '2026-07-09'],
                ['start' => '2026-07-21', 'end' => '2026-07-30'],
            ],
            '2026-07-01',
            '2026-07-30',
        );

        $this->assertSame(['min' => '2026-07-10', 'max' => '2026-07-20'], $limits);
        $this->assertFalse(AssignmentTimelineMath::rangeInsideGap('2026-07-08', '2026-07-12', $gaps));
        $this->assertTrue(AssignmentTimelineMath::rangeInsideGap('2026-07-21', '2026-07-22', $gaps));
    }

    public function test_on_site_band_starts_on_arrival_not_on_the_departure_day(): void
    {
        $this->assertSame([
            ['start' => '2026-09-18', 'end' => '2026-10-02'],
        ], AssignmentTimelineMath::onSiteBands([
            ['date' => '2026-09-18', 'kind' => 'arrival'],
            ['date' => '2026-10-02', 'kind' => 'return'],
        ], '2026-09-01', '2026-10-31'));

        $this->assertSame([
            ['start' => '2026-09-01', 'end' => '2026-09-20'],
        ], AssignmentTimelineMath::onSiteBands([
            ['date' => '2026-08-20', 'kind' => 'arrival'],
            ['date' => '2026-09-20', 'kind' => 'return'],
        ], '2026-09-01', '2026-10-31'));

        $this->assertSame([], AssignmentTimelineMath::onSiteBands([
            ['date' => '2026-09-18', 'kind' => 'arrival'],
        ], '2026-09-01', '2026-09-10'));
    }
}
