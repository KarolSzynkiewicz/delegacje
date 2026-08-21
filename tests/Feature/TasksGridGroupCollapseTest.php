<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Livewire\TasksGrid;
use App\Models\ProjectTask;
use App\Models\Sprint;
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

    public function test_group_chevron_matches_task_expand_and_hides_poza_sprintem(): void
    {
        $hidden = ProjectTask::query()->create([
            'name' => 'Zadanie poza sprintem',
            'status' => TaskStatus::PENDING,
            'created_by' => $this->user->id,
            'sprint_id' => null,
        ]);
        $shown = $this->makeTask('Zadanie ze sprintem', 'Inne', Sprint::factory()->create()->id);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->set('groupBy', 'sprint')
            ->assertSee('Zadanie poza sprintem')
            ->assertSeeHtml("toggleGroupCollapse('".md5('')."')")
            ->call('toggleGroupCollapse', md5(''))
            ->assertDontSee('Zadanie poza sprintem')
            ->assertSee('Zadanie ze sprintem')
            ->assertSee('Poza sprintem');

        $this->assertNull($hidden->fresh()->sprint_id);
        $this->assertSame(TaskStatus::PENDING, $hidden->fresh()->status);
        $this->assertNotNull($shown->fresh()->sprint_id);
    }

    private function makeTask(string $name, string $category, ?int $sprintId = null): ProjectTask
    {
        return ProjectTask::query()->create([
            'name' => $name,
            'category' => $category,
            'status' => TaskStatus::PENDING,
            'created_by' => $this->user->id,
            'sprint_id' => $sprintId,
        ]);
    }
}
