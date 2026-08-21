<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Livewire\SprintBoard;
use App\Livewire\TasksGrid;
use App\Models\ProjectTask;
use App\Models\Sprint;
use App\Models\TaskSubtask;
use App\Models\User;
use App\Services\SprintInsights;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class SprintTest extends TestCase
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

    public function test_sprint_can_have_many_tasks(): void
    {
        $sprint = Sprint::factory()->create([
            'name' => 'Sprint 1',
            'goal' => 'Dowieźć onboarding',
            'definition_of_done' => 'Zmergowane i na produkcji',
        ]);

        $taskA = $this->makeTask('Zadanie A', ['sprint_id' => $sprint->id]);
        $taskB = $this->makeTask('Zadanie B', ['sprint_id' => $sprint->id]);

        $this->assertCount(2, $sprint->tasks);
        $this->assertTrue($taskA->fresh()->sprint->is($sprint));
        $this->assertSame('Dowieźć onboarding', $sprint->goal);
        $this->assertSame('Zmergowane i na produkcji', $sprint->definition_of_done);
        $this->assertTrue($sprint->end_date->gte($sprint->start_date));
        $this->assertSame($sprint->id, $taskB->fresh()->sprint_id);
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('sprints', 'definition_of_ready'));
    }

    public function test_sprint_board_page_renders(): void
    {
        $sprint = Sprint::factory()->create(['name' => 'HQ Sprint']);
        $this->makeTask('Kartka', ['sprint_id' => $sprint->id]);

        $this->actingAs($this->user)
            ->get(route('sprints.show', $sprint))
            ->assertOk()
            ->assertSee('HQ Sprint')
            ->assertSee('Kartka')
            ->assertSee('Burndown')
            ->assertSee('Backlog sprintu')
            ->assertSee('Komentarze')
            ->assertSee('Historia')
            ->assertDontSee('Definition of Ready');
    }

    public function test_deleting_sprint_unassigns_tasks(): void
    {
        $sprint = Sprint::factory()->create();
        $task = $this->makeTask('W sprincie', ['sprint_id' => $sprint->id]);

        $sprint->delete();

        $this->assertNull($task->fresh()->sprint_id);
        $this->assertDatabaseMissing('sprints', ['id' => $sprint->id]);
    }

    public function test_admin_can_create_sprint_via_http(): void
    {
        $this->actingAs($this->user)
            ->post(route('sprints.store'), [
                'name' => 'Sprint 12',
                'goal' => 'Wdrożyć sprints',
                'definition_of_done' => 'Code review + testy',
                'start_date' => '2026-08-24',
                'end_date' => '2026-09-06',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('sprints', [
            'name' => 'Sprint 12',
            'goal' => 'Wdrożyć sprints',
            'definition_of_done' => 'Code review + testy',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_sprint_end_date_cannot_be_before_start_date(): void
    {
        $this->actingAs($this->user)
            ->post(route('sprints.store'), [
                'name' => 'Zły sprint',
                'start_date' => '2026-09-06',
                'end_date' => '2026-08-24',
            ])
            ->assertSessionHasErrors('end_date');
    }

    public function test_dragging_task_between_sprint_groups_assigns_sprint(): void
    {
        $from = Sprint::factory()->create(['name' => 'Sprint A']);
        $to = Sprint::factory()->create(['name' => 'Sprint B']);
        $task = $this->makeTask('Do przeniesienia', ['sprint_id' => $from->id]);
        $this->makeTask('Kotwica B', ['sprint_id' => $to->id]);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->set('groupBy', 'sprint')
            ->call('moveTaskToGroup', $task->id, (string) $to->id);

        $this->assertSame($to->id, $task->fresh()->sprint_id);
        $this->assertNotNull($task->fresh()->sprint_position);
    }

    public function test_task_can_be_moved_out_of_sprint(): void
    {
        $sprint = Sprint::factory()->create();
        $task = $this->makeTask('W sprincie', ['sprint_id' => $sprint->id, 'sprint_position' => 1]);
        $this->makeTask('Poza sprintem', ['sprint_id' => null]);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->set('groupBy', 'sprint')
            ->call('moveTaskToGroup', $task->id, '');

        $this->assertNull($task->fresh()->sprint_id);
        $this->assertNull($task->fresh()->sprint_position);
    }

    public function test_locked_grid_shows_only_this_sprint_tasks(): void
    {
        $sprint = Sprint::factory()->create();
        $other = Sprint::factory()->create();
        $this->makeTask('W tym sprincie', ['sprint_id' => $sprint->id]);
        $this->makeTask('W innym sprincie', ['sprint_id' => $other->id]);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class, ['lockedSprintId' => $sprint->id])
            ->assertSee('W tym sprincie')
            ->assertDontSee('W innym sprincie');
    }

    public function test_locked_grid_adds_task_into_the_sprint(): void
    {
        $sprint = Sprint::factory()->create();

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class, ['lockedSprintId' => $sprint->id])
            ->set('newTaskName', 'Z siatki')
            ->call('addTask');

        $this->assertDatabaseHas('project_tasks', [
            'name' => 'Z siatki',
            'sprint_id' => $sprint->id,
        ]);
    }

    public function test_sprint_accepts_comments(): void
    {
        $sprint = Sprint::factory()->create();

        $this->actingAs($this->user)
            ->post(route('comments.store'), [
                'commentable_type' => 'sprint',
                'commentable_id' => $sprint->id,
                'body' => 'Trzymamy scope.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'commentable_type' => 'sprint',
            'commentable_id' => $sprint->id,
            'body' => 'Trzymamy scope.',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_board_adds_milestone_and_toggles_it(): void
    {
        $sprint = Sprint::factory()->create();

        Livewire::actingAs($this->user)
            ->test(SprintBoard::class, ['sprint' => $sprint])
            ->set('newMilestoneName', 'Demo')
            ->set('newMilestoneDue', $sprint->end_date->format('Y-m-d'))
            ->call('addMilestone')
            ->assertSee('Demo');

        $milestone = $sprint->milestones()->first();
        $this->assertNotNull($milestone);
        $this->assertNull($milestone->completed_at);

        Livewire::actingAs($this->user)
            ->test(SprintBoard::class, ['sprint' => $sprint])
            ->call('toggleMilestone', $milestone->id);

        $this->assertNotNull($milestone->fresh()->completed_at);
    }

    public function test_sprint_accepts_attachments(): void
    {
        $this->actingAs($this->user)
            ->post(route('sprints.store'), [
                'name' => 'Sprint z plikiem',
                'goal' => 'Dowieźć specyfikację',
                'start_date' => '2026-08-24',
                'end_date' => '2026-09-06',
                'attachments' => [UploadedFile::fake()->create('spec.pdf', 80, 'application/pdf')],
            ])
            ->assertRedirect();

        $sprint = Sprint::query()->where('name', 'Sprint z plikiem')->first();
        $this->assertNotNull($sprint);
        $this->assertSame(1, $sprint->attachments()->count());
        $this->assertSame('spec.pdf', $sprint->attachments()->first()->original_name);
        $this->assertSame('sprint', $sprint->attachments()->first()->attachable_type);
    }

    public function test_insights_count_progress_without_cancelled(): void
    {
        $sprint = Sprint::factory()->create([
            'start_date' => now()->subDays(3)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ]);
        $this->makeTask('Done', [
            'sprint_id' => $sprint->id,
            'status' => TaskStatus::COMPLETED,
            'completed_at' => now()->subDay(),
        ]);
        $this->makeTask('Open', ['sprint_id' => $sprint->id, 'status' => TaskStatus::PENDING]);
        $this->makeTask('Skip', ['sprint_id' => $sprint->id, 'status' => TaskStatus::CANCELLED]);

        $insights = app(SprintInsights::class)->for($sprint->fresh());

        $this->assertSame(2, $insights['scope']);
        $this->assertSame(1, $insights['done']);
        $this->assertSame(50, $insights['progress']);
        $this->assertContains($insights['health'], ['on_track', 'at_risk', 'off_track']);
    }

    public function test_sprint_activity_feed_lists_related_history(): void
    {
        $this->actingAs($this->user);

        $assignee = User::factory()->create(['name' => 'Anna']);
        $sprint = Sprint::factory()->create([
            'name' => 'HQ Sprint',
            'created_by' => $this->user->id,
        ]);
        $other = Sprint::factory()->create(['name' => 'Inny sprint']);

        $task = $this->makeTask('Kartka', [
            'sprint_id' => $sprint->id,
            'created_by' => $this->user->id,
        ]);
        $task->addComment('Zróbmy to w piątek', $this->user);

        $subtask = \App\Models\TaskSubtask::query()->create([
            'task_id' => $task->id,
            'name' => 'Napisać testy',
            'created_by' => $this->user->id,
        ]);

        $task->update(['assigned_to' => $assignee->id]);
        $subtask->markCompleted();
        $task->markCompleted();
        $task->update(['sprint_id' => $other->id, 'sprint_position' => 1]);

        $this->get(route('sprints.show', $sprint))
            ->assertOk()
            ->assertSee('Historia')
            ->assertSee('dodał zadanie')
            ->assertSee('Kartka')
            ->assertSee('dodał komentarz do')
            ->assertSee('Zróbmy to w piątek')
            ->assertSee('dodał podzadanie')
            ->assertSee('Napisać testy')
            ->assertSee('zakończył podzadanie')
            ->assertSee('zakończył zadanie')
            ->assertSee('zmienił przypisanie')
            ->assertSee('Anna')
            ->assertSee('przeniósł zadanie ze sprintu')
            ->assertSee('Inny sprint');

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => ProjectTask::class,
            'auditable_id' => $task->id,
            'event' => 'created',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => \App\Models\Comment::class,
            'event' => 'created',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => \App\Models\TaskSubtask::class,
            'auditable_id' => $subtask->id,
            'event' => 'created',
        ]);
    }

    public function test_tasks_grid_logs_subtask_lifecycle(): void
    {
        $this->actingAs($this->user);

        $sprint = Sprint::factory()->create();
        $source = $this->makeTask('Źródło', ['sprint_id' => $sprint->id]);
        $target = $this->makeTask('Cel', ['sprint_id' => $sprint->id]);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class, ['lockedSprintId' => $sprint->id])
            ->call('startAddSubtask', $source->id)
            ->set('newSubtaskName', 'Przenieś mnie')
            ->call('saveSubtask');

        $subtask = \App\Models\TaskSubtask::query()->where('name', 'Przenieś mnie')->first();
        $this->assertNotNull($subtask);
        $this->assertDatabaseHas('task_subtask_events', [
            'subtask_id' => $subtask->id,
            'event' => 'created',
            'user_id' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class, ['lockedSprintId' => $sprint->id])
            ->call('toggleSubtask', $subtask->id)
            ->call('moveSubtask', $subtask->id, $target->id);

        $this->assertDatabaseHas('task_subtask_events', [
            'subtask_id' => $subtask->id,
            'event' => 'completed',
        ]);
        $this->assertDatabaseHas('task_subtask_events', [
            'subtask_id' => $subtask->id,
            'event' => 'moved',
        ]);
        $this->assertSame($target->id, $subtask->fresh()->task_id);
    }

    public function test_sprint_show_query_count_stays_stable_as_tasks_grow(): void
    {
        $this->actingAs($this->user);

        $countFor = function (int $n): int {
            $sprint = Sprint::factory()->create(['created_by' => $this->user->id]);
            for ($i = 0; $i < $n; $i++) {
                $task = $this->makeTask("Zadanie {$n}-{$i}", [
                    'sprint_id' => $sprint->id,
                    'assigned_to' => $this->user->id,
                ]);
                $task->addComment("komentarz {$i}", $this->user);
                TaskSubtask::query()->create([
                    'task_id' => $task->id,
                    'name' => "Pod {$i}",
                    'created_by' => $this->user->id,
                ]);
            }

            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get(route('sprints.show', $sprint))->assertOk();

            return count(DB::getQueryLog());
        };

        $small = $countFor(3);
        $large = $countFor(12);

        $this->assertLessThan(
            15,
            $large - $small,
            "Przyrost zapytań {$small} → {$large} wygląda na N+1"
        );
    }

    public function test_sprint_show_does_not_lazy_load_relations(): void
    {
        $this->actingAs($this->user);

        $sprint = Sprint::factory()->create(['created_by' => $this->user->id]);
        $task = $this->makeTask('Kartka', [
            'sprint_id' => $sprint->id,
            'assigned_to' => $this->user->id,
        ]);
        $task->addComment('Hej', $this->user);
        TaskSubtask::query()->create([
            'task_id' => $task->id,
            'name' => 'Krok',
            'created_by' => $this->user->id,
        ]);

        Model::preventLazyLoading();

        try {
            $this->get(route('sprints.show', $sprint))->assertOk();
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeTask(string $name, array $overrides = []): ProjectTask
    {
        return ProjectTask::query()->create(array_merge([
            'name' => $name,
            'status' => TaskStatus::PENDING,
            'created_by' => $this->user->id,
        ], $overrides));
    }
}
