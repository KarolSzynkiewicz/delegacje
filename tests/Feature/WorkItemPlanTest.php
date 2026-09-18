<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Enums\WorkItemTimeBlockKind;
use App\Enums\WorkItemType;
use App\Livewire\TasksGrid;
use App\Livewire\WorkItemPlan;
use App\Models\ApprovalRequest;
use App\Models\ProcedureTemplate;
use App\Models\ProjectTask;
use App\Models\User;
use App\Models\WorkItem;
use App\Models\WorkItemTimeBlock;
use App\Services\WorkItemPlanService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WorkItemPlanTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->user = User::factory()->create(['name' => 'Karol']);
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $this->user->assignRole($adminRole);
        }

        Carbon::setTestNow(Carbon::parse('2026-09-17 12:00:00'));
    }

    public function test_plan_page_renders_queue_and_week(): void
    {
        $this->actingAs($this->user)
            ->get(route('work-items.plan'))
            ->assertOk()
            ->assertSee('Do przypięcia')
            ->assertSee('Plan')
            ->assertSee('07:00')
            ->assertSee('20:00');
    }

    public function test_open_item_without_block_is_in_the_queue(): void
    {
        $this->actingAs($this->user);
        $this->task('WI bez bloku');

        $queue = app(WorkItemPlanService::class)->queue($this->user, now());

        $this->assertTrue($queue->contains(fn (WorkItem $item) => $item->title === 'WI bez bloku'));
    }

    public function test_todays_block_keeps_the_card_off_the_queue(): void
    {
        $this->actingAs($this->user);
        $item = $this->workItem('Z blokiem dziś');

        WorkItemTimeBlock::query()->create([
            'work_item_id' => $item->id,
            'user_id' => $this->user->id,
            'starts_at' => '2026-09-17 08:00:00',
            'ends_at' => '2026-09-17 08:30:00',
            'created_by_id' => $this->user->id,
        ]);

        $queue = app(WorkItemPlanService::class)->queue($this->user, now());

        $this->assertFalse($queue->contains(fn (WorkItem $item) => $item->title === 'Z blokiem dziś'));
    }

    public function test_yesterdays_block_returns_open_card_to_the_queue(): void
    {
        $this->actingAs($this->user);
        $item = $this->workItem('Wczorajszy blok, niezrobione');

        WorkItemTimeBlock::query()->create([
            'work_item_id' => $item->id,
            'user_id' => $this->user->id,
            'starts_at' => '2026-09-16 10:00:00',
            'ends_at' => '2026-09-16 10:30:00',
            'created_by_id' => $this->user->id,
        ]);

        $queue = app(WorkItemPlanService::class)->queue($this->user, now());

        $this->assertTrue($queue->contains(fn (WorkItem $item) => $item->title === 'Wczorajszy blok, niezrobione'));
    }

    public function test_dropping_a_queue_item_creates_a_thirty_minute_block(): void
    {
        $this->actingAs($this->user);
        $item = $this->workItem('Do przypięcia');

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('dropOnCell', 'queue', $item->id, '2026-09-17', 10 * 60)
            ->assertHasNoErrors();

        $block = WorkItemTimeBlock::query()->where('work_item_id', $item->id)->first();
        $this->assertNotNull($block);
        $this->assertSame('2026-09-17 10:00:00', $block->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-17 10:30:00', $block->ends_at->format('Y-m-d H:i:s'));
        $this->assertSame($this->user->id, $block->user_id);
    }

    public function test_new_block_does_not_overwrite_a_past_one(): void
    {
        $this->actingAs($this->user);
        $item = $this->workItem('Dwa bloki');

        WorkItemTimeBlock::query()->create([
            'work_item_id' => $item->id,
            'user_id' => $this->user->id,
            'starts_at' => '2026-09-16 10:00:00',
            'ends_at' => '2026-09-16 10:30:00',
            'created_by_id' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('dropOnCell', 'queue', $item->id, '2026-09-17', 9 * 60);

        $this->assertSame(2, WorkItemTimeBlock::query()->where('work_item_id', $item->id)->count());
    }

    public function test_meeting_with_a_slot_is_on_the_grid_not_in_the_queue(): void
    {
        $this->actingAs($this->user);
        $this->meeting('Spotkanie rekrutacyjne', '2026-09-17 08:00:00', '2026-09-17 09:00:00');

        $service = app(WorkItemPlanService::class);
        $queue = $service->queue($this->user, now());
        $this->assertFalse($queue->contains(fn (WorkItem $item) => $item->title === 'Spotkanie rekrutacyjne'));

        $weekStart = $service->weekStart(now());
        $events = collect($service->occupancy($this->user, $weekStart, now())['timed']['2026-09-17']);
        $this->assertTrue($events->contains(fn ($event) => $event->kind === 'meeting' && $event->title === 'Spotkanie rekrutacyjne'));
    }

    public function test_completed_task_block_is_not_on_the_grid(): void
    {
        $this->actingAs($this->user);
        $item = $this->workItem('Zakończone na kalendarzu');
        WorkItemTimeBlock::query()->create([
            'work_item_id' => $item->id,
            'user_id' => $this->user->id,
            'starts_at' => '2026-09-17 08:00:00',
            'ends_at' => '2026-09-17 08:30:00',
            'created_by_id' => $this->user->id,
        ]);

        $task = ProjectTask::query()->findOrFail($item->source_id);
        $task->markCompleted();
        $item->refresh();
        $this->assertFalse($item->status->isOpen());

        $service = app(WorkItemPlanService::class);
        $events = collect($service->occupancy($this->user, $service->weekStart(now()), now())['timed']['2026-09-17'] ?? []);
        $this->assertFalse($events->contains(fn ($event) => $event->title === 'Zakończone na kalendarzu'));
    }

    public function test_completed_meeting_is_not_on_the_grid(): void
    {
        $this->actingAs($this->user);
        $task = $this->meeting('Spotkanie rekrutacyjne', '2026-09-17 08:00:00', '2026-09-17 09:00:00');
        $task->markCompleted();

        $service = app(WorkItemPlanService::class);
        $events = collect($service->occupancy($this->user, $service->weekStart(now()), now())['timed']['2026-09-17'] ?? []);
        $this->assertFalse($events->contains(fn ($event) => $event->title === 'Spotkanie rekrutacyjne'));
    }

    public function test_dropping_a_meeting_writes_the_source_slot(): void
    {
        $this->actingAs($this->user);
        $task = $this->task('Spotkanie: sync');
        $item = WorkItem::query()->where('source_id', $task->id)->firstOrFail();

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('dropOnCell', 'queue', $item->id, '2026-09-18', 11 * 60);

        $task->refresh();
        $this->assertTrue($task->isMeeting());
        $this->assertSame('2026-09-18 11:00:00', $task->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-18 11:30:00', $task->ends_at->format('Y-m-d H:i:s'));
        $this->assertSame(0, WorkItemTimeBlock::query()->count());
    }

    public function test_dropping_a_meeting_from_the_queue_removes_it_from_the_left_list(): void
    {
        $this->actingAs($this->user);
        $task = $this->task('Spotkanie: znika z kolejki');
        $item = WorkItem::query()->where('source_id', $task->id)->firstOrFail();

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->assertSee('data-plan-drag="queue:'.$item->id.'"', false)
            ->call('dropOnCell', 'queue', $item->id, '2026-09-18', 11 * 60)
            ->assertDontSee('data-plan-drag="queue:'.$item->id.'"', false)
            ->assertSee('data-plan-drag="meeting:'.$item->id.'"', false);
    }

    public function test_json_stringify_of_wire_does_not_hit_a_missing_to_json_method(): void
    {
        $this->actingAs($this->user);

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('toJSON')
            ->assertHasNoErrors();

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class, [
                'planQueue' => true,
                'planUserId' => $this->user->id,
            ])
            ->call('toJSON')
            ->assertHasNoErrors();
    }

    public function test_pin_query_shows_only_that_queue_item(): void
    {
        $this->actingAs($this->user);
        $this->workItem('Inne zadanie z kolejki');
        $pinned = $this->workItem('Spotkanie: tylko to');

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class, ['pinId' => $pinned->id])
            ->assertSee('Spotkanie: tylko to')
            ->assertSee('przeciągnij na godzinę', false)
            ->assertDontSee('Inne zadanie z kolejki');

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->assertSee('Spotkanie: tylko to')
            ->assertSee('Inne zadanie z kolejki');
    }

    public function test_plan_queue_embeds_backlog_cards_with_locked_overlay(): void
    {
        $this->actingAs($this->user);
        $mine = $this->workItem('Kartka z kolejki Planu');
        $blocked = $this->workItem('Z blokiem dziś — poza kolejką');
        WorkItemTimeBlock::query()->create([
            'work_item_id' => $blocked->id,
            'user_id' => $this->user->id,
            'starts_at' => '2026-09-17 08:00:00',
            'ends_at' => '2026-09-17 08:30:00',
            'created_by_id' => $this->user->id,
        ]);

        $other = User::factory()->create(['name' => 'Marek']);
        ProjectTask::query()->create([
            'name' => 'Zadanie Marka poza overlay',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $other->id,
            'created_by' => $other->id,
        ]);

        $this->get(route('work-items.plan'))
            ->assertOk()
            ->assertSeeLivewire(TasksGrid::class)
            ->assertSee('Kartka z kolejki Planu')
            ->assertSee('data-plan-queue-grip', false)
            ->assertSee('data-plan-drag="queue:'.$mine->id.'"', false)
            ->assertDontSee('Zadanie Marka poza overlay');

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class, [
                'planQueue' => true,
                'planUserId' => $this->user->id,
            ])
            ->assertSee('Kartka z kolejki Planu')
            ->assertDontSee('Z blokiem dziś — poza kolejką')
            ->assertDontSee('Zadanie Marka poza overlay')
            ->call('clearFilters')
            ->assertSee('Kartka z kolejki Planu')
            ->assertDontSee('Z blokiem dziś — poza kolejką')
            ->assertDontSee('Zadanie Marka poza overlay')
            ->assertSee('Do przypięcia')
            ->set('selectedTypes', ['meeting'])
            ->assertDontSee('Kartka z kolejki Planu');
    }

    public function test_opening_a_queue_item_embeds_the_task_card(): void
    {
        $this->actingAs($this->user);
        $item = $this->workItem('Otwarte z kolejki');

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('openEvent', 'item', $item->id)
            ->assertHasNoErrors()
            ->assertSeeLivewire(\App\Livewire\TaskShowQuickEdit::class)
            ->assertSee('Otwarte z kolejki')
            ->assertSee('Bez godziny')
            ->assertDontSeeHtml('wire:click="unschedule');
    }

    public function test_unscheduled_meeting_stays_in_the_queue_until_pinned_with_hours(): void
    {
        $this->actingAs($this->user);

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('openUnscheduledMeeting')
            ->assertSet('composerUnscheduled', true)
            ->set('composerTitle', 'Sync bez godziny')
            ->call('submitComposer')
            ->assertHasNoErrors();

        $task = ProjectTask::query()->where('name', 'Spotkanie: Sync bez godziny')->first();
        $this->assertNotNull($task);
        $this->assertTrue($task->isMeeting());
        $this->assertNull($task->starts_at);

        $item = WorkItem::query()->where('source_id', $task->id)->first();
        $this->assertNotNull($item);
        $this->assertSame(WorkItemType::Meeting, $item->type);

        $queue = app(WorkItemPlanService::class)->queue($this->user, now());
        $this->assertTrue($queue->contains(fn (WorkItem $row) => $row->id === $item->id));

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('dropOnCell', 'queue', $item->id, '2026-09-17', 0, true)
            ->assertHasNoErrors();

        $this->assertNull($task->fresh()->starts_at);

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('dropOnCell', 'queue', $item->id, '2026-09-17', 14 * 60)
            ->assertHasNoErrors();

        $task->refresh();
        $this->assertSame('2026-09-17 14:00:00', $task->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-17 14:30:00', $task->ends_at->format('Y-m-d H:i:s'));
    }

    public function test_unschedule_deletes_a_block_not_the_work_item(): void
    {
        $this->actingAs($this->user);
        $item = $this->workItem('Zdejmij mnie');
        $block = WorkItemTimeBlock::query()->create([
            'work_item_id' => $item->id,
            'user_id' => $this->user->id,
            'starts_at' => '2026-09-17 10:00:00',
            'ends_at' => '2026-09-17 10:30:00',
            'created_by_id' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('unschedule', $block->id);

        $this->assertDatabaseMissing('work_item_time_blocks', ['id' => $block->id]);
        $this->assertDatabaseHas('work_items', ['id' => $item->id]);
    }

    public function test_due_flag_is_separate_from_blocks(): void
    {
        $this->actingAs($this->user);
        $this->task('Kompletacja z terminem', '2026-09-18');

        $flags = app(WorkItemPlanService::class)->dueFlags($this->user, now());

        $this->assertNotEmpty($flags['2026-09-18'] ?? []);
        $this->assertSame('Kompletacja z terminem', $flags['2026-09-18'][0]['title']);
    }

    public function test_yesterdays_block_can_be_moved_to_today(): void
    {
        $this->actingAs($this->user);
        $item = $this->workItem('Przesuń z wczoraj');
        $block = WorkItemTimeBlock::query()->create([
            'work_item_id' => $item->id,
            'user_id' => $this->user->id,
            'starts_at' => '2026-09-16 10:00:00',
            'ends_at' => '2026-09-16 11:00:00',
            'created_by_id' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('dropOnCell', 'block', $block->id, '2026-09-17', 9 * 60);

        $block->refresh();
        $this->assertSame('2026-09-17 09:00:00', $block->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame(1, WorkItemTimeBlock::query()->where('work_item_id', $item->id)->count());

        $queue = app(WorkItemPlanService::class)->queue($this->user, now());
        $this->assertFalse($queue->contains(fn (WorkItem $row) => $row->id === $item->id));
    }

    public function test_all_day_drop_does_not_use_the_hour_grid(): void
    {
        $this->actingAs($this->user);
        $item = $this->workItem('Na dziś bez godziny');

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('dropOnCell', 'queue', $item->id, '2026-09-17', 0, true);

        $block = WorkItemTimeBlock::query()->where('work_item_id', $item->id)->first();
        $this->assertTrue($block->all_day);
        $allDay = app(WorkItemPlanService::class)->occupancy($this->user, now(), now())['allDay']['2026-09-17'];
        $this->assertTrue(collect($allDay)->contains(fn ($event) => $event->workItemId === $item->id));
    }

    public function test_composer_creates_a_timed_task(): void
    {
        $this->actingAs($this->user);

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('openComposer', '2026-09-18', 10 * 60, 11 * 60, false)
            ->set('composerTitle', 'Nowe z siatki')
            ->set('composerType', 'task')
            ->call('submitComposer')
            ->assertHasNoErrors();

        $item = WorkItem::query()->where('title', 'Nowe z siatki')->first();
        $this->assertNotNull($item);
        $block = WorkItemTimeBlock::query()->where('work_item_id', $item->id)->first();
        $this->assertNotNull($block);
        $this->assertSame('2026-09-18 10:00:00', $block->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-18 11:00:00', $block->ends_at->format('Y-m-d H:i:s'));
    }

    public function test_click_composer_defaults_to_fifteen_minutes(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('openComposer', '2026-09-18', 12 * 60 + 15, 12 * 60 + 15, false);

        $this->assertSame(12 * 60 + 15, $component->get('composerStart'));
        $this->assertSame(12 * 60 + 30, $component->get('composerEnd'));

        $component
            ->set('composerTitle', 'Kwadrans z kliknięcia')
            ->set('composerType', 'task')
            ->call('submitComposer')
            ->assertHasNoErrors();

        $item = WorkItem::query()->where('title', 'Kwadrans z kliknięcia')->first();
        $block = WorkItemTimeBlock::query()->where('work_item_id', $item->id)->first();
        $this->assertSame('2026-09-18 12:15:00', $block->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-18 12:30:00', $block->ends_at->format('Y-m-d H:i:s'));
    }

    public function test_upward_selection_keeps_the_click_as_the_end(): void
    {
        $this->actingAs($this->user);

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('openComposer', '2026-09-18', 11 * 60 + 45, 12 * 60 + 15, false)
            ->set('composerTitle', 'W górę')
            ->set('composerType', 'task')
            ->call('submitComposer')
            ->assertHasNoErrors();

        $item = WorkItem::query()->where('title', 'W górę')->first();
        $block = WorkItemTimeBlock::query()->where('work_item_id', $item->id)->first();
        $this->assertSame('2026-09-18 11:45:00', $block->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-18 12:15:00', $block->ends_at->format('Y-m-d H:i:s'));
    }

    public function test_overlapping_events_share_a_cluster_not_the_whole_day(): void
    {
        $this->actingAs($this->user);
        $morning = $this->workItem('Rano');
        $evening = $this->workItem('Wieczór');
        WorkItemTimeBlock::query()->create([
            'work_item_id' => $morning->id,
            'user_id' => $this->user->id,
            'starts_at' => '2026-09-17 08:00:00',
            'ends_at' => '2026-09-17 09:00:00',
            'created_by_id' => $this->user->id,
        ]);
        WorkItemTimeBlock::query()->create([
            'work_item_id' => $evening->id,
            'user_id' => $this->user->id,
            'starts_at' => '2026-09-17 16:00:00',
            'ends_at' => '2026-09-17 17:00:00',
            'created_by_id' => $this->user->id,
        ]);

        $events = collect(app(WorkItemPlanService::class)->occupancy($this->user, now(), now())['timed']['2026-09-17']);
        $this->assertTrue($events->every(fn ($event) => $event->laneCount === 1));
    }

    public function test_fifteen_minute_block_occupies_a_quarter_hour_on_the_grid(): void
    {
        $this->actingAs($this->user);
        $short = $this->workItem('Kwadrans');
        $next = $this->workItem('Dalej');
        WorkItemTimeBlock::query()->create([
            'work_item_id' => $short->id,
            'user_id' => $this->user->id,
            'starts_at' => '2026-09-17 08:00:00',
            'ends_at' => '2026-09-17 08:15:00',
            'created_by_id' => $this->user->id,
        ]);
        WorkItemTimeBlock::query()->create([
            'work_item_id' => $next->id,
            'user_id' => $this->user->id,
            'starts_at' => '2026-09-17 08:15:00',
            'ends_at' => '2026-09-17 08:45:00',
            'created_by_id' => $this->user->id,
        ]);

        $events = collect(app(WorkItemPlanService::class)->occupancy($this->user, now(), now())['timed']['2026-09-17']);
        $quarter = $events->first(fn ($event) => $event->title === 'Kwadrans');
        $following = $events->first(fn ($event) => $event->title === 'Dalej');

        $this->assertNotNull($quarter);
        $this->assertNotNull($following);
        $this->assertTrue($quarter->isCompact());
        $this->assertTrue($quarter->hidesTime());
        $this->assertFalse($following->isCompact());
        $this->assertTrue($following->hidesTime());
        $this->assertEqualsWithDelta(15 / 1440 * 100, $quarter->heightPercent, 0.05);
        $this->assertLessThanOrEqual($following->topPercent + 0.01, $quarter->topPercent + $quarter->heightPercent);
    }

    public function test_calendar_event_renders_move_grip_and_resize_corner(): void
    {
        $this->actingAs($this->user);
        $item = $this->workItem('Kafelek z chwytem');
        WorkItemTimeBlock::query()->create([
            'work_item_id' => $item->id,
            'user_id' => $this->user->id,
            'starts_at' => '2026-09-17 10:00:00',
            'ends_at' => '2026-09-17 11:00:00',
            'created_by_id' => $this->user->id,
        ]);

        $this->get(route('work-items.plan'))
            ->assertOk()
            ->assertSee('Kafelek z chwytem')
            ->assertSee('wi-plan__grip', false)
            ->assertSee('wi-plan__resize', false)
            ->assertSee('beginOpen', false);
    }

    public function test_composer_approval_is_timed_and_can_be_unscheduled(): void
    {
        $this->actingAs($this->user);

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('openComposer', '2026-09-18', 13 * 60, 15 * 60, false)
            ->set('composerTitle', 'Faktura do podpisu')
            ->set('composerType', 'approval')
            ->call('submitComposer')
            ->assertHasNoErrors();

        $approval = ApprovalRequest::query()->where('name', 'Faktura do podpisu')->first();
        $this->assertNotNull($approval);
        $this->assertNull($approval->due_at);

        $item = WorkItem::query()->where('type', WorkItemType::Approval)->where('source_id', $approval->id)->firstOrFail();
        $block = WorkItemTimeBlock::query()->where('work_item_id', $item->id)->first();
        $this->assertFalse((bool) $block->all_day);
        $this->assertSame('2026-09-18 13:00:00', $block->starts_at->format('Y-m-d H:i:s'));

        $flags = app(WorkItemPlanService::class)->dueFlags($this->user, now());
        $this->assertFalse(collect($flags['2026-09-18'] ?? [])->contains(fn ($flag) => $flag['title'] === 'Faktura do podpisu'));

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('unschedule', $block->id);

        $this->assertDatabaseMissing('work_item_time_blocks', ['id' => $block->id]);
        $queue = app(WorkItemPlanService::class)->queue($this->user, now());
        $this->assertTrue($queue->contains(fn (WorkItem $row) => $row->id === $item->id));
    }

    public function test_all_day_block_can_move_to_a_timed_slot(): void
    {
        $this->actingAs($this->user);
        $item = $this->workItem('Zatwierdzenie całodniowe');
        $block = WorkItemTimeBlock::query()->create([
            'work_item_id' => $item->id,
            'user_id' => $this->user->id,
            'starts_at' => '2026-09-17 00:00:00',
            'ends_at' => '2026-09-17 23:59:59',
            'all_day' => true,
            'created_by_id' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('dropOnCell', 'block', $block->id, '2026-09-17', 9 * 60, false);

        $block->refresh();
        $this->assertFalse((bool) $block->all_day);
        $this->assertSame('2026-09-17 09:00:00', $block->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-17 09:30:00', $block->ends_at->format('Y-m-d H:i:s'));
    }

    public function test_undo_restores_a_moved_block(): void
    {
        $this->actingAs($this->user);
        $item = $this->workItem('Przypadkowy drop');
        $block = WorkItemTimeBlock::query()->create([
            'work_item_id' => $item->id,
            'user_id' => $this->user->id,
            'starts_at' => '2026-09-17 10:00:00',
            'ends_at' => '2026-09-17 11:00:00',
            'created_by_id' => $this->user->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('dropOnCell', 'block', $block->id, '2026-09-18', 12 * 60)
            ->assertSee('Cofnij');

        $block->refresh();
        $this->assertSame('2026-09-18 12:00:00', $block->starts_at->format('Y-m-d H:i:s'));

        $component->call('undoLastChange')->assertSet('undo', null);

        $block->refresh();
        $this->assertSame('2026-09-17 10:00:00', $block->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-17 11:00:00', $block->ends_at->format('Y-m-d H:i:s'));
    }

    public function test_undo_restores_an_unscheduled_session(): void
    {
        $this->actingAs($this->user);
        $session = $this->planSession('Worek', '2026-09-17 14:00:00', '2026-09-17 16:00:00');
        $item = $this->workItem('Oddzwonić do Oli');
        $session->items()->attach($item->id);

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('unschedule', $session->id)
            ->call('undoLastChange');

        $restored = WorkItemTimeBlock::query()->where('kind', WorkItemTimeBlockKind::Session)->first();
        $this->assertNotNull($restored);
        $this->assertSame('2026-09-17 14:00:00', $restored->starts_at->format('Y-m-d H:i:s'));
        $this->assertEqualsCanonicalizing([$item->id], $restored->items()->pluck('work_items.id')->all());
    }

    public function test_copying_a_block_keeps_the_original_and_adds_another_slot(): void
    {
        $this->actingAs($this->user);
        $item = $this->workItem('Dwa sloty');
        $block = WorkItemTimeBlock::query()->create([
            'work_item_id' => $item->id,
            'user_id' => $this->user->id,
            'starts_at' => '2026-09-17 10:00:00',
            'ends_at' => '2026-09-17 11:00:00',
            'created_by_id' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('dropOnCell', 'block', $block->id, '2026-09-18', 14 * 60, false, true)
            ->assertHasNoErrors();

        $block->refresh();
        $this->assertSame('2026-09-17 10:00:00', $block->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-17 11:00:00', $block->ends_at->format('Y-m-d H:i:s'));

        $copy = WorkItemTimeBlock::query()
            ->where('work_item_id', $item->id)
            ->where('id', '!=', $block->id)
            ->first();
        $this->assertNotNull($copy);
        $this->assertSame('2026-09-18 14:00:00', $copy->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-18 15:00:00', $copy->ends_at->format('Y-m-d H:i:s'));
        $this->assertSame(2, WorkItemTimeBlock::query()->where('work_item_id', $item->id)->count());
    }

    public function test_evening_slot_is_not_clamped_to_eighteen(): void
    {
        $this->actingAs($this->user);
        $item = $this->workItem('Wieczorny blok');

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('dropOnCell', 'queue', $item->id, '2026-09-17', 20 * 60);

        $block = WorkItemTimeBlock::query()->where('work_item_id', $item->id)->first();
        $this->assertSame('2026-09-17 20:00:00', $block->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-17 20:30:00', $block->ends_at->format('Y-m-d H:i:s'));
    }

    public function test_composer_meeting_keeps_location_and_other_participants(): void
    {
        $this->actingAs($this->user);
        $guest = User::factory()->create(['name' => 'Anna']);

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('openComposer', '2026-09-18', 10 * 60, 11 * 60, false)
            ->set('composerType', 'meeting')
            ->set('composerTitle', 'Sync z Anną')
            ->set('composerLocation', 'Sala 2 / Gdańsk')
            ->set('composerParticipantIds', [$this->user->id, $guest->id])
            ->call('submitComposer')
            ->assertHasNoErrors();

        $task = ProjectTask::query()->where('name', 'Sync z Anną')->first();
        $this->assertNotNull($task);
        $this->assertSame('Sala 2 / Gdańsk', $task->location);
        $this->assertEqualsCanonicalizing([$this->user->id, $guest->id], $task->participant_ids);
        $this->assertSame('2026-09-18 10:00:00', $task->starts_at->format('Y-m-d H:i:s'));
    }

    public function test_composer_starts_a_procedure_on_the_selected_slot(): void
    {
        $this->actingAs($this->user);
        $template = ProcedureTemplate::query()->create([
            'name' => 'Onboarding',
            'created_by' => $this->user->id,
            'definition' => [
                'nodes' => [
                    ['id' => 'start-1', 'type' => 'start', 'name' => 'Start'],
                    ['id' => 'step-1', 'type' => 'task', 'name' => 'Krok'],
                ],
                'edges' => [
                    ['id' => 'e1', 'from' => 'start-1', 'to' => 'step-1'],
                ],
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('openComposer', '2026-09-18', 14 * 60, 15 * 60, false)
            ->set('composerType', 'procedure')
            ->set('composerProcedureTemplateId', (string) $template->id)
            ->set('composerProcedureNameSuffix', 'Jan')
            ->call('submitComposer')
            ->assertHasNoErrors();

        $item = WorkItem::query()->where('type', WorkItemType::ProcedureRun)->first();
        $this->assertNotNull($item);
        $this->assertSame('Onboarding · Jan', $item->title);
        $block = WorkItemTimeBlock::query()->where('work_item_id', $item->id)->first();
        $this->assertNotNull($block);
        $this->assertSame('2026-09-18 14:00:00', $block->starts_at->format('Y-m-d H:i:s'));
    }

    public function test_plan_preview_embeds_the_procedure_stepper(): void
    {
        $this->actingAs($this->user);
        $template = ProcedureTemplate::query()->create([
            'name' => 'Onboarding',
            'created_by' => $this->user->id,
            'definition' => [
                'nodes' => [
                    ['id' => 'start-1', 'type' => 'start', 'name' => 'Start'],
                    ['id' => 'step-1', 'type' => 'task', 'name' => 'Krok do kliknięcia'],
                ],
                'edges' => [
                    ['id' => 'e1', 'from' => 'start-1', 'to' => 'step-1'],
                ],
            ],
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('openComposer', '2026-09-18', 14 * 60, 15 * 60, false)
            ->set('composerType', 'procedure')
            ->set('composerProcedureTemplateId', (string) $template->id)
            ->call('submitComposer')
            ->assertHasNoErrors();

        $item = WorkItem::query()->where('type', WorkItemType::ProcedureRun)->firstOrFail();
        $block = WorkItemTimeBlock::query()->where('work_item_id', $item->id)->firstOrFail();

        $component->call('openEvent', 'block', $block->id)
            ->assertSeeLivewire(\App\Livewire\ProcedureRunStepper::class)
            ->assertSee('Postęp');
    }

    public function test_composer_creates_an_empty_session_on_the_slot(): void
    {
        $this->actingAs($this->user);

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('openComposer', '2026-09-17', 14 * 60, 16 * 60, false)
            ->set('composerType', 'session')
            ->set('composerTitle', 'Dzwonienie')
            ->call('submitComposer')
            ->assertHasNoErrors();

        $block = WorkItemTimeBlock::query()->where('kind', WorkItemTimeBlockKind::Session)->first();
        $this->assertNotNull($block);
        $this->assertNull($block->work_item_id);
        $this->assertSame('Dzwonienie', $block->title);
        $this->assertSame('2026-09-17 14:00:00', $block->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-17 16:00:00', $block->ends_at->format('Y-m-d H:i:s'));
        $this->assertSame(0, $block->items()->count());

        $events = collect(app(WorkItemPlanService::class)->occupancy($this->user, now(), now())['timed']['2026-09-17']);
        $this->assertTrue($events->contains(fn ($event) => $event->isSession && $event->title === 'Dzwonienie'));
    }

    public function test_selected_queue_items_join_one_session_in_bulk(): void
    {
        $this->actingAs($this->user);
        $session = $this->planSession('Dzwonienie', '2026-09-17 14:00:00', '2026-09-17 16:00:00');
        $first = $this->workItem('Oddzwonić do Ani');
        $second = $this->workItem('Oddzwonić do Bartka');

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('addQueueItemsToSession', [$first->id, $second->id], $session->id)
            ->assertHasNoErrors();

        $this->assertEqualsCanonicalizing(
            [$first->id, $second->id],
            $session->fresh()->items->pluck('id')->all()
        );
    }

    public function test_dropping_a_selected_queue_bundle_creates_a_session_without_meetings(): void
    {
        $this->actingAs($this->user);
        $first = $this->workItem('Oddzwonić do Ani');
        $second = $this->workItem('Oddzwonić do Bartka');
        $meeting = $this->workItem('Spotkanie: sync');

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('dropQueueBundle', [$first->id, $second->id, $meeting->id], '2026-09-17', 14 * 60)
            ->assertHasNoErrors();

        $session = WorkItemTimeBlock::query()->where('kind', WorkItemTimeBlockKind::Session)->first();
        $this->assertNotNull($session);
        $this->assertSame('2026-09-17 14:00:00', $session->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-17 14:30:00', $session->ends_at->format('Y-m-d H:i:s'));
        $this->assertEqualsCanonicalizing(
            [$first->id, $second->id],
            $session->items()->pluck('work_items.id')->all()
        );
        $this->assertSame(0, $meeting->fresh()->sessionBlocks()->count());
        $this->assertNull($meeting->fresh()->source?->starts_at);
    }

    public function test_queue_items_join_a_session_instead_of_making_new_blocks(): void
    {
        $this->actingAs($this->user);
        $session = $this->planSession('Dzwonienie', '2026-09-17 14:00:00', '2026-09-17 16:00:00');
        $first = $this->workItem('Oddzwonić do Ani');
        $second = $this->workItem('Oddzwonić do Bartka');
        $other = $this->workItem('Pilny mail');

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('addToSession', $session->id, $first->id)
            ->call('addToSession', $session->id, $second->id)
            ->assertHasNoErrors();

        $this->assertSame(1, WorkItemTimeBlock::query()->count());
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $session->items()->pluck('work_items.id')->all());

        $queue = app(WorkItemPlanService::class)->queue($this->user, now());
        $this->assertFalse($queue->contains(fn (WorkItem $row) => $row->id === $first->id));
        $this->assertFalse($queue->contains(fn (WorkItem $row) => $row->id === $second->id));
        $this->assertTrue($queue->contains(fn (WorkItem $row) => $row->id === $other->id));

        $events = collect(app(WorkItemPlanService::class)->occupancy($this->user, now(), now())['timed']['2026-09-17']);
        $card = $events->first(fn ($event) => $event->isSession);
        $this->assertSame('Dzwonienie', $card->title);
        $this->assertSame('2 zadania', $card->typeLabel);

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('removeFromSession', $session->id, $first->id)
            ->assertHasNoErrors();

        $queue = app(WorkItemPlanService::class)->queue($this->user, now());
        $this->assertTrue($queue->contains(fn (WorkItem $row) => $row->id === $first->id));
        $this->assertFalse($queue->contains(fn (WorkItem $row) => $row->id === $second->id));

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('dropOnCell', 'queue', $other->id, '2026-09-17', 14 * 60)
            ->assertHasNoErrors();

        $this->assertSame(2, WorkItemTimeBlock::query()->count());
        $this->assertTrue(WorkItemTimeBlock::query()->where('work_item_id', $other->id)->exists());
    }

    public function test_meetings_cannot_join_a_session(): void
    {
        $this->actingAs($this->user);
        $session = $this->planSession('Dzwonienie', '2026-09-17 14:00:00', '2026-09-17 16:00:00');
        $task = $this->task('Spotkanie: sync');
        $item = WorkItem::query()->where('source_id', $task->id)->firstOrFail();

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('addToSession', $session->id, $item->id);

        $this->assertSame(0, $session->items()->count());
        $this->assertNull($task->fresh()->starts_at);
    }

    public function test_yesterdays_session_returns_members_to_the_queue(): void
    {
        $this->actingAs($this->user);
        $session = $this->planSession('Wczorajsze dzwonienie', '2026-09-16 14:00:00', '2026-09-16 16:00:00');
        $item = $this->workItem('Oddzwonić do Igi');
        $session->items()->attach($item->id);

        $queue = app(WorkItemPlanService::class)->queue($this->user, now());
        $this->assertTrue($queue->contains(fn (WorkItem $row) => $row->id === $item->id));
    }

    public function test_copying_a_session_copies_its_members(): void
    {
        $this->actingAs($this->user);
        $session = $this->planSession('Dzwonienie', '2026-09-17 10:00:00', '2026-09-17 12:00:00');
        $item = $this->workItem('Oddzwonić do Oli');
        $session->items()->attach($item->id);

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('dropOnCell', 'block', $session->id, '2026-09-18', 14 * 60, false, true)
            ->assertHasNoErrors();

        $copy = WorkItemTimeBlock::query()
            ->where('kind', WorkItemTimeBlockKind::Session)
            ->where('id', '!=', $session->id)
            ->first();
        $this->assertNotNull($copy);
        $this->assertSame('2026-09-18 14:00:00', $copy->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-18 16:00:00', $copy->ends_at->format('Y-m-d H:i:s'));
        $this->assertEqualsCanonicalizing([$item->id], $copy->items()->pluck('work_items.id')->all());
        $this->assertEqualsCanonicalizing([$item->id], $session->fresh()->items()->pluck('work_items.id')->all());
    }

    public function test_opening_a_block_embeds_the_existing_task_card(): void
    {
        $this->actingAs($this->user);
        $item = $this->workItem('auto - porysowany bok');
        $block = WorkItemTimeBlock::query()->create([
            'work_item_id' => $item->id,
            'user_id' => $this->user->id,
            'starts_at' => '2026-09-15 12:45:00',
            'ends_at' => '2026-09-15 14:30:00',
            'created_by_id' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('openEvent', 'block', $block->id)
            ->assertHasNoErrors()
            ->assertSeeLivewire(\App\Livewire\TaskShowQuickEdit::class)
            ->assertSeeLivewire(\App\Livewire\TaskSubtasks::class)
            ->assertSee('Szczegóły')
            ->assertSee('auto - porysowany bok')
            ->assertSee('12:45–14:30')
            ->assertSee('Odplanuj')
            ->assertSee('Dziennik operacyjny')
            ->assertSee('Podgląd z Planera')
            ->assertDontSee('Poprzednie')
            ->assertDontSee('Otwórz kartę')
            ->call('closeEvent')
            ->assertDontSeeLivewire(\App\Livewire\TaskShowQuickEdit::class);
    }

    public function test_completed_session_members_drop_out_of_the_bag(): void
    {
        $this->actingAs($this->user);
        $session = $this->planSession('Dzwonienie', '2026-09-17 10:00:00', '2026-09-17 12:00:00');
        $keepA = $this->workItem('Zadzwonić do Ani');
        $keepB = $this->workItem('Zadzwonić do Basi');
        $done = $this->workItem('Zadzwonić do Celiny');
        $session->items()->attach([$keepA->id, $keepB->id, $done->id]);

        $task = ProjectTask::query()->findOrFail($done->source_id);
        $task->markCompleted();
        $done->refresh();
        $this->assertFalse($done->status->isOpen());

        $service = app(WorkItemPlanService::class);
        $events = collect($service->occupancy($this->user, $service->weekStart(now()), now())['timed']['2026-09-17'] ?? []);
        $slot = $events->first(fn ($event) => $event->isSession && $event->blockId === $session->id);
        $this->assertNotNull($slot);
        $this->assertCount(2, $slot->members);
        $this->assertSame('2 zadania', $slot->typeLabel);
        $this->assertFalse(collect($slot->members)->contains(fn ($member) => $member['title'] === 'Zadzwonić do Celiny'));

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('openEvent', 'block', $session->id)
            ->assertSee('Zadzwonić do Ani')
            ->assertSee('Zadzwonić do Basi')
            ->assertDontSee('Zadzwonić do Celiny');
    }

    public function test_opening_a_meeting_embeds_the_task_card_without_unschedule(): void
    {
        $this->actingAs($this->user);
        $task = $this->meeting('Spotkanie rekrutacyjne', '2026-09-17 08:00:00', '2026-09-17 09:00:00');
        $item = WorkItem::query()->where('source_type', 'project_task')->where('source_id', $task->id)->firstOrFail();

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('openEvent', 'meeting', $item->id)
            ->assertHasNoErrors()
            ->assertSeeLivewire(\App\Livewire\TaskShowQuickEdit::class)
            ->assertDontSeeLivewire(\App\Livewire\TaskSubtasks::class)
            ->assertSee('Spotkanie')
            ->assertDontSeeHtml('wire:click="unschedule');
    }

    public function test_opening_a_session_lists_members_then_opens_their_card(): void
    {
        $this->actingAs($this->user);
        $session = $this->planSession('Dzwonienie', '2026-09-17 10:00:00', '2026-09-17 12:00:00');
        $item = $this->workItem('Oddzwonić do Igi');
        $session->items()->attach($item->id);

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('openEvent', 'block', $session->id)
            ->assertHasNoErrors()
            ->assertDontSeeLivewire(\App\Livewire\TaskShowQuickEdit::class)
            ->assertSee('Oddzwonić do Igi')
            ->assertSee('Sesja')
            ->call('openSessionMember', $item->id)
            ->assertSeeLivewire(\App\Livewire\TaskShowQuickEdit::class)
            ->assertSee('Szczegóły')
            ->assertSee('← Sesja');
    }

    public function test_unschedule_closes_the_plan_card_dialog(): void
    {
        $this->actingAs($this->user);
        $item = $this->workItem('Zdejmij z dialogu');
        $block = WorkItemTimeBlock::query()->create([
            'work_item_id' => $item->id,
            'user_id' => $this->user->id,
            'starts_at' => '2026-09-17 10:00:00',
            'ends_at' => '2026-09-17 10:30:00',
            'created_by_id' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('openEvent', 'block', $block->id)
            ->assertSeeLivewire(\App\Livewire\TaskShowQuickEdit::class)
            ->call('unschedule', $block->id)
            ->assertDontSeeLivewire(\App\Livewire\TaskShowQuickEdit::class);

        $this->assertDatabaseMissing('work_item_time_blocks', ['id' => $block->id]);
    }

    public function test_opening_an_approval_block_embeds_the_approval_card(): void
    {
        $this->actingAs($this->user);

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('openComposer', '2026-09-18', 13 * 60, 15 * 60, false)
            ->set('composerTitle', 'Faktura do podpisu')
            ->set('composerType', 'approval')
            ->call('submitComposer')
            ->assertHasNoErrors();

        $item = WorkItem::query()->where('type', WorkItemType::Approval)->where('title', 'Faktura do podpisu')->firstOrFail();
        $block = WorkItemTimeBlock::query()->where('work_item_id', $item->id)->firstOrFail();

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('openEvent', 'block', $block->id)
            ->assertDontSeeLivewire(\App\Livewire\TaskShowQuickEdit::class)
            ->assertSee('Faktura do podpisu')
            ->assertSee('Zatwierdzający')
            ->assertSee('Zatwierdź')
            ->assertSee('Uzasadnienie (opcjonalnie)')
            ->assertDontSee('Otwórz kartę');
    }

    private function planSession(string $title, string $starts, string $ends): WorkItemTimeBlock
    {
        return WorkItemTimeBlock::query()->create([
            'work_item_id' => null,
            'kind' => WorkItemTimeBlockKind::Session,
            'title' => $title !== '' ? $title : null,
            'user_id' => $this->user->id,
            'starts_at' => $starts,
            'ends_at' => $ends,
            'created_by_id' => $this->user->id,
        ]);
    }

    private function task(string $name, ?string $due = null): ProjectTask
    {
        return ProjectTask::query()->create([
            'name' => $name,
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
            'due_date' => $due,
        ]);
    }

    private function workItem(string $name, ?string $due = null): WorkItem
    {
        $task = $this->task($name, $due);

        return WorkItem::query()->where('source_type', 'project_task')->where('source_id', $task->id)->firstOrFail();
    }

    private function meeting(string $name, string $starts, string $ends): ProjectTask
    {
        return ProjectTask::query()->create([
            'name' => $name,
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
            'starts_at' => $starts,
            'ends_at' => $ends,
            'due_date' => Carbon::parse($starts)->toDateString(),
            'participant_ids' => [$this->user->id],
        ]);
    }
}
