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
}
