<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Livewire\TasksGrid;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TasksGridGroupCollapseTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->user = User::factory()->create(['name' => 'karol']);
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $this->user->assignRole($adminRole);
        }
    }

    public function test_collapsing_a_group_hides_its_rows_without_changing_tasks(): void
    {
        $hidden = $this->makeTask('Ukrywane zadanie A', 'Podzadanie');
        $visible = $this->makeTask('Widoczne zadanie C', 'Komentarz');

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->set('groupBy', 'category')
            ->assertSee('Ukrywane zadanie A')
            ->assertSee('Widoczne zadanie C')
            ->call('toggleGroupCollapse', 'Podzadanie')
            ->assertDontSee('Ukrywane zadanie A')
            ->assertSee('Widoczne zadanie C')
            ->assertSee('Podzadanie');

        $this->assertSame('Podzadanie', $hidden->fresh()->category);
        $this->assertSame('Komentarz', $visible->fresh()->category);
        $this->assertSame(TaskStatus::PENDING, $hidden->fresh()->status);
    }

    public function test_collapsing_again_shows_the_group_rows(): void
    {
        $this->makeTask('Ukrywane zadanie A', 'Podzadanie');

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->set('groupBy', 'category')
            ->call('toggleGroupCollapse', 'Podzadanie')
            ->assertDontSee('Ukrywane zadanie A')
            ->call('toggleGroupCollapse', 'Podzadanie')
            ->assertSee('Ukrywane zadanie A');
    }

    public function test_group_chevron_matches_task_expand_and_hides_brak_projektu(): void
    {
        $hidden = ProjectTask::query()->create([
            'name' => 'Zadanie bez projektu',
            'status' => TaskStatus::PENDING,
            'created_by' => $this->user->id,
            'project_id' => null,
        ]);
        $shown = $this->makeTask('Zadanie z projektem', 'Inne');

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->set('groupBy', 'project')
            ->assertSee('Zadanie bez projektu')
            ->assertSeeHtml("toggleGroupCollapse('".md5('Brak projektu')."')")
            ->call('toggleGroupCollapse', md5('Brak projektu'))
            ->assertDontSee('Zadanie bez projektu')
            ->assertSee('Zadanie z projektem')
            ->assertSee('Brak projektu');

        $this->assertNull($hidden->fresh()->project_id);
        $this->assertSame(TaskStatus::PENDING, $hidden->fresh()->status);
        $this->assertNotNull($shown->fresh()->project_id);
    }

    private function makeTask(string $name, string $category): ProjectTask
    {
        return ProjectTask::query()->create([
            'name' => $name,
            'category' => $category,
            'status' => TaskStatus::PENDING,
            'created_by' => $this->user->id,
            'project_id' => Project::factory()->create()->id,
        ]);
    }
}
