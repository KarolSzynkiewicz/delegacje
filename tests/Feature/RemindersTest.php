<?php

namespace Tests\Feature;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEvent;
use App\Enums\TaskStatus;
use App\Enums\WorkItemTimeBlockKind;
use App\Enums\WorkItemType;
use App\Livewire\NotificationPreferences;
use App\Livewire\TasksGrid;
use App\Models\NotificationPreference;
use App\Models\ProjectTask;
use App\Models\User;
use App\Models\WorkItem;
use App\Models\WorkItemTimeBlock;
use App\Notifications\BlockReminder;
use App\Notifications\DeadlineReminder;
use App\Support\ReminderDispatcher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\TestCase;

class RemindersTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);
        $this->user = User::factory()->create(['name' => 'Karol']);
        $role = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($role) {
            $this->user->assignRole($role);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_timed_block_sends_once_and_again_after_it_moves(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-10-13 08:46:00');
        $block = $this->block('2026-10-13 09:00:00', '2026-10-13 10:00:00');

        $this->assertTrue($block->remind);
        app(ReminderDispatcher::class)->dispatch();
        app(ReminderDispatcher::class)->dispatch();

        Notification::assertSentToTimes($this->user, BlockReminder::class, 1);
        Notification::assertSentTo($this->user, BlockReminder::class, function (BlockReminder $notification, array $channels): bool {
            return $channels === ['database', WebPushChannel::class]
                && $notification->toDatabase($this->user)['message'] === 'Za 15 minut: Blok testowy';
        });

        $block->update([
            'starts_at' => '2026-10-13 10:00:00',
            'ends_at' => '2026-10-13 11:00:00',
        ]);
        Carbon::setTestNow('2026-10-13 09:50:00');
        app(ReminderDispatcher::class)->dispatch();

        Notification::assertSentToTimes($this->user, BlockReminder::class, 2);
    }

    public function test_silent_completed_and_already_started_blocks_stay_quiet(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-10-13 08:46:00');

        $silent = $this->block('2026-10-13 09:00:00', '2026-10-13 10:00:00');
        $silent->update(['remind' => false]);

        $done = $this->task('Skończone');
        $done->update(['status' => TaskStatus::COMPLETED]);
        $this->blockOn($done, '2026-10-13 09:00:00', '2026-10-13 10:00:00');

        app(ReminderDispatcher::class)->dispatch();
        Notification::assertNothingSent();

        Carbon::setTestNow('2026-10-13 09:03:00');
        $this->block('2026-10-13 09:00:00', '2026-10-13 10:00:00', 'Spóźniony');
        app(ReminderDispatcher::class)->dispatch();

        Notification::assertNothingSent();
    }

    public function test_all_day_block_and_deadline_use_the_profile_clock(): void
    {
        Notification::fake();
        $this->user->forceFill(['deadline_reminder_time' => '11:15:00'])->save();

        $task = $this->task('Termin');
        $task->update(['due_date' => '2026-10-13']);
        $this->blockOn($task, '2026-10-13 00:00:00', '2026-10-13 23:59:59', allDay: true);

        Carbon::setTestNow('2026-10-13 11:14:00');
        app(ReminderDispatcher::class)->dispatch();
        Notification::assertNothingSent();

        Carbon::setTestNow('2026-10-13 11:15:00');
        app(ReminderDispatcher::class)->dispatch();
        app(ReminderDispatcher::class)->dispatch();

        Notification::assertSentToTimes($this->user, BlockReminder::class, 1);
        Notification::assertSentToTimes($this->user, DeadlineReminder::class, 1);
        Notification::assertSentTo($this->user, BlockReminder::class, function (BlockReminder $notification): bool {
            return $notification->toDatabase($this->user)['message'] === 'Dziś w planie: Termin';
        });
        Notification::assertSentTo($this->user, DeadlineReminder::class, function (DeadlineReminder $notification): bool {
            return $notification->toDatabase($this->user)['message'] === 'Dziś mija termin: Termin';
        });

        $task->update(['due_date' => '2026-10-14']);
        Carbon::setTestNow('2026-10-14 11:15:00');
        app(ReminderDispatcher::class)->dispatch();
        Notification::assertSentToTimes($this->user, DeadlineReminder::class, 2);
    }

    public function test_meeting_reminds_people_in_the_room_and_skips_the_deadline(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-10-13 09:00:00');
        $guest = User::factory()->create(['name' => 'Gość']);
        $outsider = User::factory()->create(['name' => 'Obcy']);

        $meeting = ProjectTask::createIntended(WorkItemType::Meeting, [
            'name' => 'Status',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
            'starts_at' => '2026-10-13 09:10:00',
            'ends_at' => '2026-10-13 09:40:00',
            'due_date' => '2026-10-13',
            'participant_ids' => [$guest->id],
        ]);

        app(ReminderDispatcher::class)->dispatch();
        app(ReminderDispatcher::class)->dispatch();

        Notification::assertSentToTimes($this->user, BlockReminder::class, 1);
        Notification::assertSentToTimes($guest, BlockReminder::class, 1);
        Notification::assertNotSentTo($outsider, BlockReminder::class);
        Notification::assertNotSentTo($this->user, DeadlineReminder::class);
        $this->assertNotNull($meeting->fresh()->reminder_sent_for);
    }

    public function test_turned_off_channels_wait_and_a_stranger_cannot_toggle(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-10-13 08:46:00');
        $block = $this->block('2026-10-13 09:00:00', '2026-10-13 10:00:00');

        foreach ([NotificationChannel::Database, NotificationChannel::Push] as $channel) {
            NotificationPreference::query()->create([
                'user_id' => $this->user->id,
                'event_type' => NotificationEvent::BlockReminder,
                'channel' => $channel,
                'enabled' => false,
            ]);
        }

        app(ReminderDispatcher::class)->dispatch();
        Notification::assertNothingSent();
        $this->assertNull($block->fresh()->reminder_sent_for);

        NotificationPreference::query()->where('user_id', $this->user->id)->delete();
        app(ReminderDispatcher::class)->dispatch();
        Notification::assertSentToTimes($this->user, BlockReminder::class, 1);

        $stranger = User::factory()->create();
        Livewire::actingAs($stranger)
            ->test(TasksGrid::class)
            ->call('toggleSlotReminder', 'block:'.$block->id)
            ->assertForbidden();

        $this->assertTrue($block->fresh()->remind);
    }

    public function test_grid_bell_starts_lit_and_hides_after_the_second_click(): void
    {
        Carbon::setTestNow('2026-10-13 08:00:00');
        $task = $this->task('Widoczny blok');
        $block = $this->blockOn($task, '2026-10-13 12:15:00', '2026-10-13 13:30:00');
        $item = $task->workItem()->firstOrFail();

        $this->assertSame([
            [
                'label' => '12:15–13:30',
                'key' => 'block:'.$block->id,
                'remind' => true,
            ],
        ], $item->slotReminders());

        $task->update(['status' => TaskStatus::COMPLETED]);
        $this->assertSame([], $item->fresh()->slotReminders());

        $task->update(['status' => TaskStatus::PENDING]);
        $past = $this->blockOn($task, '2026-10-13 07:00:00', '2026-10-13 07:30:00');
        $fresh = $item->fresh();
        $keys = array_column($fresh->slotReminders(), 'key');
        $this->assertContains('block:'.$block->id, $keys);
        $this->assertNotContains('block:'.$past->id, $keys);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->assertSee('Przypomnienie włączone')
            ->call('toggleSlotReminder', 'block:'.$block->id)
            ->assertSee('Włącz przypomnienie');

        $this->assertFalse($block->fresh()->remind);
    }

    public function test_profile_clock_defaults_to_nine_and_can_move(): void
    {
        Livewire::actingAs($this->user)
            ->test(NotificationPreferences::class)
            ->assertSet('deadlineReminderTime', '09:00')
            ->assertSee('Przypomnienie o terminie')
            ->assertSee('15 minut przed startem')
            ->set('deadlineReminderTime', '10:30')
            ->assertSet('deadlineReminderTime', '10:30');

        $this->assertSame('10:30', $this->user->fresh()->reminderClock());
    }

    private function task(string $name): ProjectTask
    {
        return ProjectTask::query()->create([
            'name' => $name,
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);
    }

    private function block(string $starts, string $ends, string $name = 'Blok testowy'): WorkItemTimeBlock
    {
        return $this->blockOn($this->task($name), $starts, $ends);
    }

    private function blockOn(ProjectTask $task, string $starts, string $ends, bool $allDay = false): WorkItemTimeBlock
    {
        $item = WorkItem::query()
            ->where('source_type', $task->getMorphClass())
            ->where('source_id', $task->id)
            ->firstOrFail();

        return WorkItemTimeBlock::query()->create([
            'work_item_id' => $item->id,
            'kind' => WorkItemTimeBlockKind::Item,
            'user_id' => $this->user->id,
            'starts_at' => $starts,
            'ends_at' => $ends,
            'all_day' => $allDay,
            'created_by_id' => $this->user->id,
        ]);
    }
}
