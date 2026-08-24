<?php

namespace Tests\Feature;

use App\Livewire\TasksGrid;
use App\Models\TaskGridView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TasksGridSavedViewsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->user = User::factory()->create(['name' => 'Admin']);
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $this->user->assignRole($adminRole);
        }

        $this->other = User::factory()->create(['name' => 'Kolega']);
    }

    public function test_changing_a_filter_detaches_the_view_instead_of_overwriting_it(): void
    {
        $record = $this->createView($this->user, [
            'name' => 'Wszystkie',
            'slug' => 'wszystkie',
            'status' => 'all',
        ]);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->call('loadView', 'wszystkie')
            ->assertSet('view', 'wszystkie')
            ->assertSet('status', 'all')
            ->set('status', 'closed')
            ->assertSet('view', '')
            ->assertSet('activeViewId', null)
            ->assertSet('status', 'closed');

        $this->assertSame('all', $record->fresh()->status);
    }

    public function test_overwrite_view_saves_current_filters_to_the_chosen_view(): void
    {
        $record = $this->createView($this->user, [
            'name' => 'Wszystkie',
            'slug' => 'wszystkie',
            'status' => 'all',
        ]);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->call('loadView', 'wszystkie')
            ->set('status', 'closed')
            ->assertSet('view', '')
            ->call('overwriteView', $record->id)
            ->assertSet('view', 'wszystkie')
            ->assertSet('activeViewId', $record->id);

        $this->assertSame('closed', $record->fresh()->status);
    }

    public function test_global_view_is_visible_and_loadable_for_another_user(): void
    {
        $this->createView($this->user, [
            'name' => 'Zespół',
            'slug' => 'zespol',
            'status' => 'closed',
            'is_global' => true,
        ]);

        Livewire::actingAs($this->other)
            ->test(TasksGrid::class)
            ->assertSee('Zespół')
            ->call('loadView', 'zespol')
            ->assertSet('view', 'zespol')
            ->assertSet('status', 'closed');
    }

    public function test_other_user_cannot_overwrite_or_delete_someone_elses_global_view(): void
    {
        $global = $this->createView($this->user, [
            'name' => 'Zespół',
            'slug' => 'zespol',
            'status' => 'closed',
            'is_global' => true,
        ]);

        Livewire::actingAs($this->other)
            ->test(TasksGrid::class)
            ->set('status', 'all')
            ->call('overwriteView', $global->id)
            ->call('deleteView', $global->id);

        $this->assertTrue($global->fresh()->exists);
        $this->assertSame('closed', $global->fresh()->status);
        $this->assertTrue($global->fresh()->is_global);
    }

    public function test_saving_as_global_makes_the_view_available_to_everyone(): void
    {
        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->set('status', 'closed')
            ->set('saveViewName', 'Dla wszystkich')
            ->set('saveViewAsGlobal', true)
            ->call('saveView');

        $record = TaskGridView::query()->where('slug', 'dla-wszystkich')->first();

        $this->assertNotNull($record);
        $this->assertTrue($record->is_global);
        $this->assertSame('closed', $record->status);

        Livewire::actingAs($this->other)
            ->test(TasksGrid::class)
            ->assertSee('Dla wszystkich');
    }

    public function test_clearing_filters_does_not_overwrite_the_saved_view(): void
    {
        $record = $this->createView($this->user, [
            'name' => 'Aktywne',
            'slug' => 'aktywne',
            'status' => '',
        ]);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->call('loadView', 'aktywne')
            ->call('clearFilters')
            ->assertSet('view', '')
            ->assertSet('status', 'all');

        $this->assertSame('', $record->fresh()->status);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function createView(User $user, array $overrides = []): TaskGridView
    {
        return TaskGridView::query()->create(array_merge([
            'user_id' => $user->id,
            'name' => 'Mój widok',
            'slug' => 'moj-widok',
            'visible_columns' => ['name', 'status'],
            'column_widths' => [],
            'status' => 'all',
            'is_global' => false,
        ], $overrides));
    }
}
