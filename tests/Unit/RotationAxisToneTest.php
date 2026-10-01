<?php

namespace Tests\Unit;

use App\Services\RotationAxisService;
use Tests\TestCase;

class RotationAxisToneTest extends TestCase
{
    public function test_tone_marks_past_active_soon_and_future(): void
    {
        $this->assertSame('past', RotationAxisService::tone('2026-09-01', '2026-09-20', '2026-09-30', '2026-10-07'));
        $this->assertSame('future', RotationAxisService::tone('2026-10-10', '2026-10-20', '2026-09-30', '2026-10-07'));
        $this->assertSame('soon', RotationAxisService::tone('2026-09-20', '2026-10-04', '2026-09-30', '2026-10-07'));
        $this->assertSame('active', RotationAxisService::tone('2026-09-20', '2026-10-20', '2026-09-30', '2026-10-07'));
        $this->assertSame('PJ', RotationAxisService::initials('Paweł', 'Jankowski'));
    }

    public function test_window_offset_shifts_half_period(): void
    {
        $today = \Carbon\Carbon::parse('2026-10-01');
        $base = RotationAxisService::windowForOffset(0, $today);
        $next = RotationAxisService::windowForOffset(1, $today);
        $prev = RotationAxisService::windowForOffset(-1, $today);

        $this->assertSame(41, $base['start']->diffInDays($base['end']));
        $this->assertSame(21, $base['start']->diffInDays($next['start']));
        $this->assertSame(21, $prev['start']->diffInDays($base['start']));
        // Okna nachodzą się o ~3 tygodnie
        $this->assertTrue($next['start']->lt($base['end']));
        $this->assertTrue($prev['end']->gt($base['start']));
    }
}
