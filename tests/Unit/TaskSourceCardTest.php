<?php

namespace Tests\Unit;

use App\Models\ProjectTask;
use App\Models\WarehouseDispatch;
use Tests\TestCase;

class TaskSourceCardTest extends TestCase
{
    public function test_recruitment_fk_still_wins_on_source_card(): void
    {
        $task = new ProjectTask(['recruitment_process_id' => 7]);

        $card = $task->sourceCard();

        $this->assertSame(route('recruitment-processes.index', ['process' => 7]), $card['url']);
        $this->assertSame('Karta kandydata', $card['label']);
        $this->assertSame('bi-person-badge', $card['icon']);
    }

    public function test_warehouse_dispatch_morph_builds_source_card(): void
    {
        $dispatch = new WarehouseDispatch(['number' => 'ZW-2026-0007']);
        $dispatch->id = 44;

        $task = new ProjectTask;
        $task->setRelation('subject', $dispatch);

        $card = $task->sourceCard();

        $this->assertSame(route('warehouse-dispatches.show', $dispatch), $card['url']);
        $this->assertSame('Dokument ZW-2026-0007', $card['label']);
        $this->assertSame('bi-box-seam', $card['icon']);
    }
}
