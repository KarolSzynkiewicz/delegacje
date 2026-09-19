<?php

namespace Tests\Feature;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEvent;
use App\Enums\TaskStatus;
use App\Livewire\NotificationBell;
use App\Livewire\NotificationPreferences;
use App\Models\NotificationPreference;
use App\Models\ProjectTask;
use App\Models\User;
use App\Notifications\MeetingInvited;
use App\Notifications\TaskAssigned;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->user = User::factory()->create(['name' => 'karol']);
        $admin = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        $this->user->assignRole($admin);
        $this->actingAs($this->user);
    }

    public function test_assigning_a_task_notifies_the_new_assignee(): void
    {
        Notification::fake();
        $robert = User::factory()->create(['name' => 'robert']);

        ProjectTask::query()->create([
            'name' => 'Pompa',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $robert->id,
            'created_by' => $this->user->id,
        ]);

        Notification::assertSentTo($robert, TaskAssigned::class);
        Notification::assertNotSentTo($this->user, TaskAssigned::class);
    }

    public function test_disabled_in_app_preference_skips_the_bell(): void
    {
        Notification::fake();
        $robert = User::factory()->create(['name' => 'robert']);

        NotificationPreference::query()->create([
            'user_id' => $robert->id,
            'event_type' => NotificationEvent::TaskAssigned->value,
            'channel' => NotificationChannel::Database->value,
            'enabled' => false,
        ]);

        ProjectTask::query()->create([
            'name' => 'Pompa',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $robert->id,
            'created_by' => $this->user->id,
        ]);

        Notification::assertNotSentTo($robert, TaskAssigned::class);
    }

    public function test_new_meeting_participants_are_invited(): void
    {
        Notification::fake();
        $robert = User::factory()->create(['name' => 'robert']);

        ProjectTask::query()->create([
            'name' => 'Spotkanie: odbiór',
            'status' => TaskStatus::PENDING,
            'starts_at' => now()->addDay(),
            'assigned_to' => $this->user->id,
            'participant_ids' => [$robert->id, $this->user->id],
            'created_by' => $this->user->id,
        ]);

        Notification::assertSentTo($robert, MeetingInvited::class);
        Notification::assertNotSentTo($this->user, MeetingInvited::class);
        Notification::assertNotSentTo($this->user, TaskAssigned::class);
    }

    public function test_opening_the_bell_does_not_mark_everything_read(): void
    {
        $robert = User::factory()->create(['name' => 'robert']);
        $this->actingAs($robert);

        $robert->notify(new TaskAssigned(
            ProjectTask::query()->create([
                'name' => 'Pompa',
                'status' => TaskStatus::PENDING,
                'created_by' => $this->user->id,
            ]),
            $this->user,
        ));

        $this->assertSame(1, $robert->unreadNotifications()->count());

        Livewire::test(NotificationBell::class)
            ->call('toggle')
            ->assertSet('open', true);

        $this->assertSame(1, $robert->unreadNotifications()->count());
    }

    public function test_opening_a_notification_marks_it_read_and_redirects(): void
    {
        $task = ProjectTask::query()->create([
            'name' => 'Pompa',
            'status' => TaskStatus::PENDING,
            'created_by' => $this->user->id,
        ]);
        $this->user->notify(new TaskAssigned($task, $this->user));
        $notification = $this->user->notifications()->first();
        $this->assertNull($notification->read_at);

        $this->get(route('notifications.open', $notification->id))
            ->assertRedirect(route('tasks.show', $task));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_profile_preferences_toggle_persists(): void
    {
        Livewire::test(NotificationPreferences::class)
            ->assertSet('enabled.'.NotificationEvent::CommentLiked->value, true)
            ->call('toggle', NotificationEvent::CommentLiked->value)
            ->assertSet('enabled.'.NotificationEvent::CommentLiked->value, false);

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->user->id,
            'event_type' => NotificationEvent::CommentLiked->value,
            'channel' => NotificationChannel::Database->value,
            'enabled' => 0,
        ]);
    }
}
