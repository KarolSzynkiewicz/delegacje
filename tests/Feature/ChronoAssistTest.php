<?php

namespace Tests\Feature;

use App\Livewire\ChronoAssist;
use App\Support\ChronoAssistCatalog;
use Livewire\Livewire;
use Tests\TestCase;

class ChronoAssistTest extends TestCase
{
    public function test_catalog_marks_wired_leaves_for_dispatch(): void
    {
        $this->assertTrue(ChronoAssistCatalog::shouldDispatch('import-json'));
        $this->assertTrue(ChronoAssistCatalog::shouldDispatch('import-list'));
        $this->assertFalse(ChronoAssistCatalog::shouldDispatch('import-prose'));
        $this->assertTrue(ChronoAssistCatalog::shouldDispatch('summary-brief'));
        $this->assertTrue(ChronoAssistCatalog::shouldDispatch('mutate-category'));
        $this->assertTrue(ChronoAssistCatalog::shouldDispatch('mutate-refine'));
        $this->assertTrue(ChronoAssistCatalog::shouldDispatch('mutate-json'));
        $this->assertTrue(ChronoAssistCatalog::shouldDispatch('mutate-export'));
        $this->assertFalse(ChronoAssistCatalog::shouldDispatch('mutate-assign'));
        $this->assertFalse(ChronoAssistCatalog::shouldDispatch('create-task'));
        $this->assertFalse(ChronoAssistCatalog::shouldDispatch('export-md'));
        $this->assertTrue(ChronoAssistCatalog::shouldDispatch('create-task', ChronoAssistCatalog::CONTEXT_TASK));
        $this->assertFalse(ChronoAssistCatalog::shouldDispatch('import-json', ChronoAssistCatalog::CONTEXT_TASK));
    }

    public function test_renders_roster_and_context(): void
    {
        Livewire::test(ChronoAssist::class, [
            'contextChips' => ['Status: Aktywne'],
            'itemCount' => 47,
        ])
            ->assertSee('Jak mogę Ci pomóc?')
            ->assertSee('Argus')
            ->assertSee('Chrono')
            ->assertSee('Impek')
            ->assertSee('Edi')
            ->assertSee('Status: Aktywne')
            ->assertSee('47')
            ->assertSee('W budowie');
    }

    public function test_pick_dispatches_only_wired_keys(): void
    {
        Livewire::test(ChronoAssist::class)
            ->call('pick', 'import-json')
            ->assertDispatched('chrono-assist-picked', key: 'import-json');

        Livewire::test(ChronoAssist::class)
            ->call('pick', 'export-csv')
            ->assertDispatched('chrono-assist-picked', key: 'export-csv');

        Livewire::test(ChronoAssist::class)
            ->call('pick', 'create-task')
            ->assertNotDispatched('chrono-assist-picked');

        Livewire::test(ChronoAssist::class, ['context' => ChronoAssistCatalog::CONTEXT_TASK])
            ->call('pick', 'create-task')
            ->assertDispatched('chrono-assist-picked', key: 'create-task');
    }

    public function test_close_dispatches_closed(): void
    {
        Livewire::test(ChronoAssist::class)
            ->call('close')
            ->assertDispatched('chrono-assist-closed');
    }
}
