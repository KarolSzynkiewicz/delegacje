<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Enums\WorkItemType;
use App\Livewire\TasksGrid;
use App\Models\ProjectTask;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SyncWorkItemsSystemActionTest extends TestCase
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

    public function test_system_actions_page_shows_the_work_item_backfill_button(): void
    {
        $this->actingAs($this->user)
            ->get(route('system-actions.index'))
            ->assertOk()
            ->assertSee('Backfill «Utworzono przez»')
            ->assertSee('Uzupełnij utworzono przez')
            ->assertSee(route('system-actions.sync-work-items'), false);
    }

    public function test_system_actions_page_shows_the_meeting_type_backfill_button(): void
    {
        $this->actingAs($this->user)
            ->get(route('system-actions.index'))
            ->assertOk()
            ->assertSee('Backfill typów spotkań i oddzwonień')
            ->assertSee('Uzupełnij typy spotkań')
            ->assertSee(route('system-actions.backfill-meeting-types'), false);
    }

    public function test_meeting_type_backfill_upgrades_legacy_heuristics_without_touching_tasks(): void
    {
        $plain = ProjectTask::query()->create([
            'name' => 'Zwykłe zadanie',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);
        $legacyMeeting = ProjectTask::query()->create([
            'name' => 'Zęby',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
            'starts_at' => now()->addDay()->setTime(10, 0),
            'ends_at' => now()->addDay()->setTime(11, 0),
        ]);
        $legacyCallback = ProjectTask::query()->create([
            'name' => 'Oddzwonić do Jan Kowalski #12',
            'category' => 'Rekrutacja',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);
        $namedMeeting = ProjectTask::query()->create([
            'name' => 'Spotkanie: Balet z Sylwią',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertSame(WorkItemType::Task, WorkItem::query()->where('source_id', $plain->id)->first()?->type);
        $this->assertSame(WorkItemType::Task, WorkItem::query()->where('source_id', $legacyMeeting->id)->first()?->type);
        $this->assertSame(WorkItemType::Task, WorkItem::query()->where('source_id', $legacyCallback->id)->first()?->type);
        $this->assertSame(WorkItemType::Task, WorkItem::query()->where('source_id', $namedMeeting->id)->first()?->type);

        $this->actingAs($this->user)
            ->post(route('system-actions.backfill-meeting-types'))
            ->assertRedirect(route('system-actions.index'))
            ->assertSessionHas('success');

        $this->assertSame(WorkItemType::Task, WorkItem::query()->where('source_id', $plain->id)->first()?->type);
        $this->assertSame(WorkItemType::Meeting, WorkItem::query()->where('source_id', $legacyMeeting->id)->first()?->type);
        $this->assertSame(WorkItemType::Callback, WorkItem::query()->where('source_id', $legacyCallback->id)->first()?->type);
        $this->assertSame(WorkItemType::Meeting, WorkItem::query()->where('source_id', $namedMeeting->id)->first()?->type);
    }

    public function test_sync_does_not_recast_a_task_that_later_gets_a_slot(): void
    {
        $task = ProjectTask::query()->create([
            'name' => 'Zwykłe zadanie ze slotem',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertSame(WorkItemType::Task, WorkItem::query()->where('source_id', $task->id)->first()?->type);

        $task->update([
            'starts_at' => now()->addDay()->setTime(10, 0),
            'ends_at' => now()->addDay()->setTime(11, 0),
        ]);

        $this->assertSame(WorkItemType::Task, WorkItem::query()->where('source_id', $task->id)->first()?->type);
        $this->assertFalse($task->fresh()->isMeeting());
    }

    public function test_backfill_reindexes_tasks_missing_from_work_items(): void
    {
        $task = ProjectTask::query()->create([
            'name' => 'Stare zadanie sprzed indeksu',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        WorkItem::query()->delete();

        $this->assertSame(0, WorkItem::query()->count());

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->assertDontSee('Stare zadanie sprzed indeksu');

        $this->actingAs($this->user)
            ->post(route('system-actions.sync-work-items'))
            ->assertRedirect(route('system-actions.index'))
            ->assertSessionHas('success');

        $item = WorkItem::query()
            ->where('source_type', 'project_task')
            ->where('source_id', $task->id)
            ->first();

        $this->assertNotNull($item);
        $this->assertSame(WorkItemType::Task, $item->type);
        $this->assertSame('Stare zadanie sprzed indeksu', $item->title);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->assertSee('Stare zadanie sprzed indeksu');
    }
}
