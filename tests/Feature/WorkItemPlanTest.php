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

    public function test_pin_opens_the_assignee_calendar_even_when_another_user_is_selected(): void
    {
        $this->actingAs($this->user);
        $ola = User::factory()->create(['name' => 'Ola Plan']);
        $item = $this->workItem('U Oli');
        $item->source->update(['assigned_to' => $ola->id]);
        $item->refresh();

        $this->assertStringContainsString('u='.$ola->id, $item->planPinUrl());

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class, [
                'userId' => $this->user->id,
                'pinId' => $item->id,
            ])
            ->assertSet('userId', $ola->id)
            ->assertSee('U Oli')
            ->assertSee('przeciągnij na godzinę', false);
    }

    public function test_pin_without_assignee_explains_that_someone_must_be_assigned(): void
    {
        $this->actingAs($this->user);
        $item = $this->workItem('Emitenci bez osoby');
        $item->source->update(['assigned_to' => null]);
        $this->workItem('Inne zadanie z kolejki');

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class, ['pinId' => $item->id])
            ->assertSee('Emitenci bez osoby')
            ->assertSee('nie ma przypisanej osoby')
            ->assertDontSee('przeciągnij na godzinę', false)
            ->assertSee('Inne zadanie z kolejki');
    }

    public function test_unassigned_schedule_chip_starts_assignee_edit(): void
    {
        $this->actingAs($this->user);
        $item = $this->workItem('Bez osoby');
        $item->source->update(['assigned_to' => null]);

        Livewire::test(TasksGrid::class)
            ->assertSee('Brak')
            ->assertSeeHtml("startEdit({$item->id}, 'assigned_to')")
            ->assertDontSeeHtml('pin='.$item->id);
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
            ->assertDontSee('data-plan-queue-grip', false)
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

    public function test_plan_queue_meetings_have_no_selection_checkbox(): void
    {
        $this->actingAs($this->user);
        $item = $this->workItem('Wymienic rolety w sypialni');
        $meeting = ProjectTask::query()->create([
            'name' => 'Spotkanie: urodziny Szymona i Aldony',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);
        $meetingItem = WorkItem::query()
            ->where('source_type', 'project_task')
            ->where('source_id', $meeting->id)
            ->firstOrFail();
        $this->assertTrue($meetingItem->isMeetingItem());

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class, [
                'planQueue' => true,
                'planUserId' => $this->user->id,
            ])
            ->assertSee('Wymienic rolety w sypialni')
            ->assertSee('Spotkanie: urodziny Szymona i Aldony')
            ->assertSeeHtml('id="tg-sel-'.$item->id.'"')
            ->assertDontSeeHtml('toggleSelected('.$item->id.')')
            ->assertDontSeeHtml('toggleSelected('.$meetingItem->id.')')
            ->call('toggleSelected', $meetingItem->id)
            ->assertSet('selectedIds', [])
            ->call('toggleSelectVisible')
            ->assertSet('selectedIds', [$item->id]);
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

    public function test_unscheduled_queue_actions_assign_to_the_calendar_user(): void
    {
        $this->actingAs($this->user);
        $ania = User::factory()->create(['name' => 'Ania']);
        $template = ProcedureTemplate::query()->create([
            'name' => 'Onboarding z kolejki',
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
            ->assertSee('Dodaj zadanie')
            ->assertSee('Uruchom procedurę')
            ->assertSee('Umów spotkanie')
            ->set('userId', $ania->id)
            ->call('openUnscheduledComposer', 'task')
            ->assertSet('composerUnscheduled', true)
            ->assertSet('composerType', 'task')
            ->set('composerTitle', 'Szybkie zadanie Ani')
            ->call('submitComposer')
            ->assertHasNoErrors();

        $task = ProjectTask::query()->where('name', 'Szybkie zadanie Ani')->first();
        $this->assertNotNull($task);
        $this->assertSame($ania->id, $task->assigned_to);
        $this->assertDatabaseMissing('work_item_time_blocks', [
            'work_item_id' => WorkItem::query()->where('source_id', $task->id)->value('id'),
        ]);

        $queue = app(WorkItemPlanService::class)->queue($ania, now());
        $this->assertTrue($queue->contains(fn (WorkItem $row) => $row->title === 'Szybkie zadanie Ani'));

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->set('userId', $ania->id)
            ->call('openUnscheduledComposer', 'procedure')
            ->set('composerProcedureTemplateId', (string) $template->id)
            ->set('composerProcedureNameSuffix', 'Ania')
            ->call('submitComposer')
            ->assertHasNoErrors();

        $item = WorkItem::query()->where('type', WorkItemType::ProcedureRun)->where('title', 'Onboarding z kolejki · Ania')->first();
        $this->assertNotNull($item);
        $this->assertSame($ania->id, $item->assignee_id);
        $this->assertDatabaseMissing('work_item_time_blocks', ['work_item_id' => $item->id]);
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
            ->call('dropQueueBundle', [$first->id, $second->id, $meeting->id], '2026-09-17', 14 * 60, false, 'Dzwonienie')
            ->assertHasNoErrors();

        $session = WorkItemTimeBlock::query()->where('kind', WorkItemTimeBlockKind::Session)->first();
        $this->assertNotNull($session);
        $this->assertSame('Dzwonienie', $session->title);
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

    public function test_session_member_preview_can_step_to_neighbors(): void
    {
        $this->actingAs($this->user);
        $session = $this->planSession('Dzwonienie', '2026-09-17 10:00:00', '2026-09-17 12:00:00');
        $first = $this->workItem('Pierwszy z worka');
        $second = $this->workItem('Drugi z worka');
        $third = $this->workItem('Trzeci z worka');
        $session->items()->attach([$first->id, $second->id, $third->id]);

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('openEvent', 'block', $session->id)
            ->call('openSessionMember', $first->id)
            ->assertSeeLivewire(\App\Livewire\TaskShowQuickEdit::class)
            ->assertSee('Pierwszy z worka')
            ->assertSee('1 / 3')
            ->assertSee('Następne')
            ->call('openNextSessionMember')
            ->assertSee('Drugi z worka')
            ->assertSee('2 / 3')
            ->call('openNextSessionMember')
            ->assertSee('Trzeci z worka')
            ->assertSee('3 / 3')
            ->call('openPrevSessionMember')
            ->assertSee('Drugi z worka');
    }

    public function test_session_title_can_be_renamed_from_the_preview(): void
    {
        $this->actingAs($this->user);
        $session = $this->planSession('', '2026-09-17 10:00:00', '2026-09-17 12:00:00');
        $this->assertNull($session->fresh()->title);

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->call('openEvent', 'block', $session->id)
            ->assertSee('Sesja')
            ->call('startSessionRename')
            ->set('sessionTitleDraft', 'Dzwonienie do leadów')
            ->call('saveSessionTitle')
            ->assertHasNoErrors()
            ->assertSee('Dzwonienie do leadów');

        $this->assertSame('Dzwonienie do leadów', $session->fresh()->title);
    }

    public function test_all_day_due_flags_start_collapsed(): void
    {
        $this->actingAs($this->user);
        $this->task('Alfa cel flagi unikat', '2026-09-18');
        $this->task('Beta cel flagi unikat', '2026-09-18');
        $this->task('Gamma cel flagi unikat', '2026-09-18');
        $this->task('Delta cel flagi unikat', '2026-09-18');

        Livewire::actingAs($this->user)
            ->test(WorkItemPlan::class)
            ->assertSee('Terminy')
            ->assertSeeHtml('wi-plan__due-toggle')
            ->assertSeeHtml('· 4')
            ->assertDontSeeHtml('wi-plan__flag-more');
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

    public function test_past_days_in_the_week_are_a_named_debt_zone(): void
    {
        $this->actingAs($this->user);
        $this->travelTo(Carbon::parse('2026-09-17 12:00:00'));

        Livewire::actingAs($this->user)
            ->withQueryParams(['w' => '2026-09-14'])
            ->test(WorkItemPlan::class)
            ->assertSee('Niedokończone')
            ->assertSee('strefa długu')
            ->assertSeeHtml('class="wi-plan__debt-banner"')
            ->assertSeeHtml('--debt-span: 3')
            ->assertSeeHtml('wi-plan__col is-debt')
            ->assertSeeHtml('wi-plan__col is-today is-debt-edge')
            ->call('nextWeek')
            ->assertDontSee('Niedokończone')
            ->assertDontSeeHtml('class="wi-plan__debt-banner"')
            ->call('previousWeek')
            ->call('previousWeek')
            ->assertSee('Niedokończone')
            ->assertSeeHtml('--debt-span: 7')
            ->assertDontSeeHtml('is-today is-debt-edge');
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

    public function test_changing_assignee_moves_item_blocks_to_the_new_person(): void
    {
        $this->actingAs($this->user);
        $item = $this->workItem('Przypisanie bloku');
        $block = WorkItemTimeBlock::query()->create([
            'work_item_id' => $item->id,
            'user_id' => $this->user->id,
            'starts_at' => '2026-09-17 08:00:00',
            'ends_at' => '2026-09-17 08:30:00',
            'created_by_id' => $this->user->id,
        ]);
        $ola = User::factory()->create(['name' => 'Ola']);

        $item->source->update(['assigned_to' => $ola->id]);

        $this->assertSame($ola->id, $block->fresh()->user_id);
        $this->assertSame($ola->id, $item->fresh()->assignee_id);
    }

    public function test_changing_assignee_detaches_the_card_from_sessions_and_keeps_the_session(): void
    {
        $this->actingAs($this->user);
        $item = $this->workItem('W sesji');
        $session = $this->planSession('Fokus', '2026-09-17 10:00:00', '2026-09-17 12:00:00');
        $session->items()->attach($item->id);
        $ola = User::factory()->create(['name' => 'Ola']);

        $item->source->update(['assigned_to' => $ola->id]);

        $this->assertFalse($session->items()->whereKey($item->id)->exists());
        $this->assertNotNull($session->fresh());
    }

    public function test_unassigning_deletes_item_blocks(): void
    {
        $this->actingAs($this->user);
        $item = $this->workItem('Bez osoby');
        $block = WorkItemTimeBlock::query()->create([
            'work_item_id' => $item->id,
            'user_id' => $this->user->id,
            'starts_at' => '2026-09-17 08:00:00',
            'ends_at' => '2026-09-17 08:30:00',
            'created_by_id' => $this->user->id,
        ]);

        $item->source->update(['assigned_to' => null]);

        $this->assertDatabaseMissing('work_item_time_blocks', ['id' => $block->id]);
        $this->assertNull($item->fresh()->assignee_id);
    }

    public function test_open_item_with_only_past_blocks_is_stale_and_pins_that_week(): void
    {
        $this->actingAs($this->user);
        $item = $this->workItem('Skisło');
        WorkItemTimeBlock::query()->create([
            'work_item_id' => $item->id,
            'user_id' => $this->user->id,
            'starts_at' => '2026-09-16 08:00:00',
            'ends_at' => '2026-09-16 08:30:00',
            'created_by_id' => $this->user->id,
        ]);
        $item->load('timeBlocks');

        $this->assertSame('stale', $item->scheduleState());
        $this->assertSame('skisło', $item->scheduleLabel());
        $this->assertSame('Zaległy · 1', $item->scheduleChipLabel());
        $this->assertStringContainsString('pin='.$item->id, $item->planPinUrl());
        $this->assertStringContainsString('w=2026-09-14', $item->planPinUrl());
    }

    public function test_grid_links_stale_blocks_to_the_plan(): void
    {
        $this->actingAs($this->user);
        $item = $this->workItem('Widok skisło');
        WorkItemTimeBlock::query()->create([
            'work_item_id' => $item->id,
            'user_id' => $this->user->id,
            'starts_at' => '2026-09-16 08:00:00',
            'ends_at' => '2026-09-16 08:30:00',
            'created_by_id' => $this->user->id,
        ]);

        Livewire::test(TasksGrid::class)
            ->assertSee('Zaległy · 1')
            ->assertSeeHtml('pin='.$item->id)
            ->assertSeeHtml('tg-schedule--stale')
            ->assertSeeHtml('tg-time-chip--stale')
            ->assertSeeHtml('bi-calendar-event');
    }

    public function test_grid_shows_schedule_and_due_chips(): void
    {
        $this->actingAs($this->user);
        $item = $this->workItem('Dwa sloty', '2026-09-24');
        foreach (['08:00:00', '10:00:00'] as $time) {
            WorkItemTimeBlock::query()->create([
                'work_item_id' => $item->id,
                'user_id' => $this->user->id,
                'starts_at' => '2026-09-17 '.$time,
                'ends_at' => '2026-09-17 '.str_replace('00:00', '30:00', $time),
                'created_by_id' => $this->user->id,
            ]);
        }
        $this->workItem('Bez terminu');
        $item->load('timeBlocks');

        $this->assertSame('Zaplanowane · 2 sloty', $item->scheduleChipLabel());

        Livewire::test(TasksGrid::class)
            ->assertSee('Zaplanowane · 2 sloty')
            ->assertSee('24.09.2026')
            ->assertSee('Brak terminu')
            ->assertSeeHtml('bi-bullseye')
            ->assertSeeHtml('tg-time-chip--due')
            ->assertSeeHtml('tg-time-chip--scheduled');
    }

    public function test_task_show_links_blocks_facet_to_the_plan(): void
    {
        $this->actingAs($this->user);
        $item = $this->workItem('Podgląd bloków');
        WorkItemTimeBlock::query()->create([
            'work_item_id' => $item->id,
            'user_id' => $this->user->id,
            'starts_at' => '2026-09-17 08:00:00',
            'ends_at' => '2026-09-17 08:30:00',
            'created_by_id' => $this->user->id,
        ]);

        Livewire::test(\App\Livewire\TaskShowQuickEdit::class, ['task' => $item->source])
            ->assertSee('W kalendarzu')
            ->assertSeeHtml('pin='.$item->id);
    }

    public function test_grid_keeps_the_bulk_bar_in_the_dom_before_selection(): void
    {
        $this->actingAs($this->user);
        $this->workItem('Zaznaczanie');

        Livewire::test(TasksGrid::class)
            ->assertSeeHtml('tg-bulk-bar')
            ->assertDontSeeHtml('x-show="count > 0"')
            ->assertSee('Co zmieniasz');
    }

    public function test_toggling_selection_dispatches_without_requiring_a_new_html_payload(): void
    {
        $this->actingAs($this->user);
        $item = $this->workItem('Skip render');

        Livewire::test(TasksGrid::class)
            ->call('toggleSelected', $item->id)
            ->assertSet('selectedIds', [$item->id])
            ->assertDispatched('tg-selection-changed', count: 1, allVisible: true);
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
