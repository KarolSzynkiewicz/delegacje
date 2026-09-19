<?php

namespace Tests\Feature;

use App\Livewire\TasksGrid;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TasksGridAddComposerTest extends TestCase
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

    public function test_composer_sits_at_the_top_and_keeps_focus_state_for_the_next_task(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->assertSeeHtml('id="tg-add-name"')
            ->assertSee('Nazwa zadania')
            ->assertSeeHtml('aria-label="Dodaj zadanie"')
            ->set('newTaskName', 'Pierwsze')
            ->set('newTaskAssignedTo', (string) $this->user->id)
            ->set('newTaskCategory', 'Flota')
            ->call('addTask')
            ->assertSet('newTaskName', '')
            ->assertSet('newTaskAssignedTo', (string) $this->user->id)
            ->assertSet('newTaskCategory', 'Flota')
            ->assertSee('Pierwsze')
            ->set('newTaskName', 'Drugie')
            ->call('addTask')
            ->assertSee('Drugie');

        $this->assertSame(2, ProjectTask::query()->whereIn('name', ['Pierwsze', 'Drugie'])->count());
        $this->assertSame('Flota', ProjectTask::query()->where('name', 'Drugie')->value('category'));
        $this->assertSame($this->user->id, ProjectTask::query()->where('name', 'Drugie')->value('assigned_to'));
        $this->assertFalse($component->get('showAddRow'));
    }
}
