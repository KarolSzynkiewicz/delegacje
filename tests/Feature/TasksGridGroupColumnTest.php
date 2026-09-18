<?php

namespace Tests\Feature;

use App\Livewire\TasksGrid;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TasksGridGroupColumnTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->user = User::factory()->create(['name' => 'Admin']);
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $this->user->assignRole($adminRole);
        }
    }

    public function test_grouping_unchecks_that_column(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(TasksGrid::class);

        $this->assertContains('status', $component->get('visibleColumns'));

        $component->call('setGroupBy', 'status');

        $this->assertSame('status', $component->get('groupBy'));
        $this->assertNotContains('status', $component->get('visibleColumns'));
        $this->assertContains('priority', $component->get('visibleColumns'));
    }

    public function test_query_string_group_by_hides_that_column(): void
    {
        $component = Livewire::actingAs($this->user)
            ->withQueryParams(['sortField' => 'priority', 'groupBy' => 'status'])
            ->test(TasksGrid::class);

        $this->assertSame('status', $component->get('groupBy'));
        $this->assertNotContains('status', $component->get('visibleColumns'));
    }

    public function test_switching_group_restores_previous_column_and_hides_the_new_one(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->call('setGroupBy', 'status')
            ->call('setGroupBy', 'sprint');

        $this->assertSame('sprint', $component->get('groupBy'));
        $this->assertContains('status', $component->get('visibleColumns'));
        $this->assertNotContains('sprint', $component->get('visibleColumns'));
    }

    public function test_clearing_group_restores_the_column(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->call('setGroupBy', 'status')
            ->call('setGroupBy', '');

        $this->assertSame('', $component->get('groupBy'));
        $this->assertContains('status', $component->get('visibleColumns'));
    }

    public function test_grouped_column_cannot_be_toggled_back_on(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->call('setGroupBy', 'status')
            ->call('toggleColumn', 'status');

        $this->assertNotContains('status', $component->get('visibleColumns'));
    }

    public function test_grouping_by_type_hides_type_column(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->call('setGroupBy', 'type');

        $this->assertSame('type', $component->get('groupBy'));
        $this->assertNotContains('type', $component->get('visibleColumns'));
    }

    public function test_type_column_can_be_hidden_from_column_picker(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(TasksGrid::class);

        $this->assertContains('type', $component->get('visibleColumns'));

        $component->call('toggleColumn', 'type');

        $this->assertNotContains('type', $component->get('visibleColumns'));

        $component->call('toggleColumn', 'type');

        $this->assertContains('type', $component->get('visibleColumns'));
    }

    public function test_active_filter_chips_show_and_can_be_removed(): void
    {
        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->set('searchTask', 'DR')
            ->set('status', 'closed')
            ->assertSee('Filtry:')
            ->assertSee('Szukaj: DR')
            ->assertSee('Status: Zamknięte')
            ->assertSee('Wyczyść')
            ->call('clearFilter', 'status')
            ->assertDontSee('Status: Zamknięte')
            ->assertSee('Szukaj: DR')
            ->call('clearFilters')
            ->assertDontSee('Szukaj: DR')
            ->assertDontSee('Filtry:');
    }

    public function test_saved_views_render_as_topbar_pills(): void
    {
        \App\Models\TaskGridView::query()->create([
            'user_id' => $this->user->id,
            'name' => 'not all cols',
            'slug' => 'not-all-cols',
            'visible_columns' => ['name', 'status'],
            'column_widths' => [],
            'status' => 'all',
        ]);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->assertSeeHtml('rp-topbar-btn')
            ->assertSee('not all cols')
            ->assertDontSee('Widoki :')
            ->call('loadView', 'not-all-cols')
            ->assertSet('view', 'not-all-cols')
            ->assertSet('status', 'all');
    }

    public function test_columns_live_on_their_own_toolbar_button(): void
    {
        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->assertSee('Filtry')
            ->assertSee('Kolumny')
            ->assertDontSee('Widoczne kolumny')
            ->assertSeeHtml('Sortuj A → Z')
            ->assertSeeHtml('Ukryj kolumnę')
            ->assertSeeHtml('ac-trigger__hint')
            ->assertSee('0 zadań');
    }

    public function test_sort_column_sets_direction_explicitly(): void
    {
        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->call('sortColumn', 'priority', 'desc')
            ->assertSet('sortField', 'priority')
            ->assertSet('sortDirection', 'desc')
            ->call('sortColumn', 'priority', 'asc')
            ->assertSet('sortDirection', 'asc');
    }

    public function test_column_filter_stays_on_that_column_and_syncs_chips(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->assertSeeHtml('data-tg-col-filter="category"')
            ->assertSeeHtml('openColFilterFromMenu')
            ->assertSeeHtml('tg-open-col-filter')
            ->assertSeeHtml('_tgDndAbort')
            ->assertDontSeeHtml('tg-col-popover-catch')
            ->assertDontSeeHtml('tg-open-filters')
            ->assertDontSeeHtml('aria-label="Filtr kolumny Kategoria"')
            ->assertSeeHtml('aria-label="Filtr kolumny Status"');

        $html = $component->html();
        $this->assertSame(1, substr_count($html, 'data-tg-col-filter="category"'));
        $this->assertSame(1, substr_count($html, 'data-tg-col-filter="assigned_to"'));
        $this->assertSame(1, substr_count($html, 'wire:model.live.debounce.300ms="searchCategory"'));

        $this->assertTrue($component->instance()->columnIsFilterable('category'));
        $this->assertFalse($component->instance()->columnIsFilterable('sprint'));
        $this->assertFalse($component->instance()->columnHasActiveFilter('category'));
        $this->assertTrue($component->instance()->columnHasActiveFilter('status'));

        $component->set('searchCategory', 'Logistyka')
            ->assertSee('Kategoria: Logistyka')
            ->assertSeeHtml('aria-label="Filtr kolumny Kategoria"');

        $this->assertTrue($component->instance()->columnHasActiveFilter('category'));

        $component->call('clearColumnFilter', 'category')
            ->assertSet('searchCategory', '')
            ->assertDontSeeHtml('aria-label="Filtr kolumny Kategoria"')
            ->assertSee('Status: Aktywne');

        $this->assertFalse($component->instance()->columnHasActiveFilter('category'));

        $component->call('filterByPriority', '4')
            ->assertSee('Priorytet: Wysoki')
            ->assertSeeHtml('aria-label="Filtr kolumny Priorytet"');

        $this->assertTrue($component->instance()->columnHasActiveFilter('priority'));
    }

    public function test_columns_chip_counts_visible_columns_in_list_order(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(TasksGrid::class);

        $visible = $component->get('visibleColumns');
        $this->assertNotEmpty($visible);
        $this->assertSame(count($visible), $component->instance()->visibleColumnCount());
        $this->assertSame($visible[0], $component->instance()->columnPickerKeys()[0]);

        $component->call('reorderColumns', 'category', 'name');

        $keys = $component->instance()->columnPickerKeys();
        $this->assertSame('category', $keys[0]);
        $this->assertSame('name', $keys[1]);
        $this->assertSame(count($component->get('visibleColumns')), $component->instance()->visibleColumnCount());
    }

    public function test_column_width_persists_without_rerendering_the_grid(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->call('setColumnWidth', 'name', 240);

        $this->assertSame(240, $component->get('columnWidths')['name']);
    }

    public function test_columns_and_grouping_return_from_cookies_on_a_fresh_mount(): void
    {
        $component = Livewire::actingAs($this->user)
            ->withCookies([
                'tg_cols' => 'name,type,priority,due_date',
                'tg_group' => 'category',
            ])
            ->test(TasksGrid::class);

        $this->assertSame('category', $component->get('groupBy'));
        $this->assertContains('priority', $component->get('visibleColumns'));
        $this->assertNotContains('created_by', $component->get('visibleColumns'));
        $this->assertNotContains('category', $component->get('visibleColumns'));
    }

    public function test_changing_columns_or_grouping_queues_cookies(): void
    {
        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->call('toggleColumn', 'created_by')
            ->call('setGroupBy', 'status');

        $cols = collect(\Illuminate\Support\Facades\Cookie::getQueuedCookies())
            ->first(fn ($cookie) => $cookie->getName() === 'tg_cols');
        $group = collect(\Illuminate\Support\Facades\Cookie::getQueuedCookies())
            ->first(fn ($cookie) => $cookie->getName() === 'tg_group');

        $this->assertNotNull($cols);
        $this->assertNotNull($group);
        $this->assertSame('status', $group->getValue());
        $this->assertStringNotContainsString('created_by', $cols->getValue());
        $this->assertStringNotContainsString('status', $cols->getValue());
    }

    public function test_query_string_group_by_wins_over_the_cookie(): void
    {
        Livewire::actingAs($this->user)
            ->withCookies(['tg_group' => 'sprint'])
            ->withQueryParams(['groupBy' => 'status'])
            ->test(TasksGrid::class)
            ->assertSet('groupBy', 'status');
    }

    public function test_plan_queue_keeps_columns_and_grouping_after_a_refresh(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(TasksGrid::class, [
                'planQueue' => true,
                'planUserId' => $this->user->id,
            ])
            ->call('toggleColumn', 'priority')
            ->call('setGroupBy', 'status');

        $this->assertSame('status', $component->get('groupBy'));
        $this->assertContains('priority', $component->get('visibleColumns'));

        $component->call('refreshPlanQueueListing');

        $this->assertSame('status', $component->get('groupBy'));
        $this->assertContains('priority', $component->get('visibleColumns'));
        $this->assertNotContains('status', $component->get('visibleColumns'));
    }

    public function test_plan_queue_restores_chrome_from_its_own_cookies(): void
    {
        $component = Livewire::actingAs($this->user)
            ->withCookies([
                'tg_plan_cols' => 'name,priority,due_date',
                'tg_plan_group' => 'category',
            ])
            ->test(TasksGrid::class, [
                'planQueue' => true,
                'planUserId' => $this->user->id,
            ]);

        $this->assertSame('category', $component->get('groupBy'));
        $this->assertContains('priority', $component->get('visibleColumns'));
        $this->assertNotContains('created_by', $component->get('visibleColumns'));
        $this->assertNotContains('category', $component->get('visibleColumns'));
    }

    public function test_bulk_apply_sets_the_chosen_field_on_selected_work_items(): void
    {
        $one = \App\Models\ProjectTask::query()->create([
            'name' => 'Pierwsze',
            'status' => \App\Enums\TaskStatus::PENDING,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);
        $two = \App\Models\ProjectTask::query()->create([
            'name' => 'Drugie',
            'status' => \App\Enums\TaskStatus::PENDING,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);
        $first = \App\Models\WorkItem::query()->where('source_id', $one->id)->firstOrFail();
        $second = \App\Models\WorkItem::query()->where('source_id', $two->id)->firstOrFail();
        $other = User::factory()->create(['name' => 'Ola']);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->call('toggleSelected', $first->id)
            ->call('toggleSelected', $second->id)
            ->set('bulkField', 'assigned_to')
            ->set('bulkValue', (string) $other->id)
            ->call('bulkApply')
            ->assertSee('Zmieniono: Przypisany');

        $this->assertSame($other->id, $one->fresh()->assigned_to);
        $this->assertSame($other->id, $two->fresh()->assigned_to);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->call('toggleSelected', $first->id)
            ->call('toggleSelected', $second->id)
            ->set('bulkField', 'category')
            ->set('bulkValue', 'Flota')
            ->call('bulkApply');

        $this->assertSame('Flota', $one->fresh()->category);
        $this->assertSame('Flota', $two->fresh()->category);
    }
}
