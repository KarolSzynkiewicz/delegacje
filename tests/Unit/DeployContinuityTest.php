<?php

namespace Tests\Unit;

use App\Support\ProductionSessionDriver;
use PHPUnit\Framework\TestCase;

class DeployContinuityTest extends TestCase
{
    public function test_production_file_sessions_move_to_the_database(): void
    {
        $this->assertSame('database', ProductionSessionDriver::resolve('production', 'file'));
    }

    public function test_explicit_session_drivers_stay_put(): void
    {
        $this->assertSame('redis', ProductionSessionDriver::resolve('production', 'redis'));
        $this->assertSame('database', ProductionSessionDriver::resolve('production', 'database'));
        $this->assertSame('file', ProductionSessionDriver::resolve('local', 'file'));
        $this->assertSame('array', ProductionSessionDriver::resolve('testing', 'array'));
    }
}
