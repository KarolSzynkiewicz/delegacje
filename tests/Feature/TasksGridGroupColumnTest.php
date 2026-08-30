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
}
