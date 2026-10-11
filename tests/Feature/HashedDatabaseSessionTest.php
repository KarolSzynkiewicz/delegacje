<?php

namespace Tests\Feature;

use App\Session\HashedDatabaseSessionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HashedDatabaseSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_row_stores_sha256_and_still_reads_back(): void
    {
        $rawId = str_repeat('a', 40);
        $handler = new HashedDatabaseSessionHandler(
            DB::connection(),
            'sessions',
            120,
            $this->app
        );

        $this->assertTrue($handler->write($rawId, 'payload-body'));
        $this->assertTrue($handler->write($rawId, 'payload-body'));

        $this->assertDatabaseHas('sessions', [
            'id' => hash('sha256', $rawId),
        ]);
        $this->assertDatabaseMissing('sessions', [
            'id' => $rawId,
        ]);
        $this->assertSame('payload-body', $handler->read($rawId));

        $this->assertTrue($handler->destroy($rawId));
        $this->assertDatabaseMissing('sessions', [
            'id' => hash('sha256', $rawId),
        ]);
        $this->assertSame('', $handler->read($rawId));
    }
}
