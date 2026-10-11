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
use NotificationChannels\WebPush\WebPushChannel;
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

        Notification::assertSentTo(
            $robert,
            TaskAssigned::class,
            fn (TaskAssigned $notification, array $channels) => $channels === [WebPushChannel::class],
        );
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
            ->assertSet('enabled.'.NotificationEvent::CommentLiked->value.'.'.NotificationChannel::Database->value, true)
            ->assertSet('enabled.'.NotificationEvent::TaskAssigned->value.'.'.NotificationChannel::Push->value, true)
            ->assertSet('enabled.'.NotificationEvent::CommentLiked->value.'.'.NotificationChannel::Push->value, false)
            ->assertSee('W aplikacji')
            ->assertSee('zaznacz wszystkie')
            ->call('toggle', NotificationEvent::CommentLiked->value, NotificationChannel::Database->value)
            ->assertSet('enabled.'.NotificationEvent::CommentLiked->value.'.'.NotificationChannel::Database->value, false);

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->user->id,
            'event_type' => NotificationEvent::CommentLiked->value,
            'channel' => NotificationChannel::Database->value,
            'enabled' => 0,
        ]);
    }

    public function test_profile_push_column_and_subscription_persist(): void
    {
        Livewire::test(NotificationPreferences::class)
            ->call('toggle', NotificationEvent::MeetingInvited->value, NotificationChannel::Push->value)
            ->assertSet('enabled.'.NotificationEvent::MeetingInvited->value.'.'.NotificationChannel::Push->value, false)
            ->call('toggleColumn', NotificationChannel::Push->value)
            ->assertSet('enabled.'.NotificationEvent::CommentLiked->value.'.'.NotificationChannel::Push->value, true)
            ->call('storePushSubscription', [
                'endpoint' => 'https://push.example.test/sub/1',
                'keys' => ['p256dh' => 'public-key', 'auth' => 'auth-token'],
                'contentEncoding' => 'aes128gcm',
            ])
            ->call('storePushSubscription', [
                'endpoint' => 'http://push.example.test/insecure',
                'keys' => ['p256dh' => 'public-key', 'auth' => 'auth-token'],
            ])
            ->assertHasErrors('endpoint');

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->user->id,
            'event_type' => NotificationEvent::CommentLiked->value,
            'channel' => NotificationChannel::Push->value,
            'enabled' => 1,
        ]);

        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_id' => $this->user->id,
            'endpoint' => 'https://push.example.test/sub/1',
            'content_encoding' => 'aes128gcm',
        ]);

        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint' => 'http://push.example.test/insecure',
        ]);

        Livewire::test(NotificationPreferences::class)
            ->call('deletePushSubscription', 'https://push.example.test/sub/1');

        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint' => 'https://push.example.test/sub/1',
        ]);
    }

    public function test_web_push_payload_uses_the_database_message_and_url(): void
    {
        $task = ProjectTask::query()->create([
            'name' => 'Pompa',
            'status' => TaskStatus::PENDING,
            'created_by' => $this->user->id,
        ]);

        $notification = new TaskAssigned($task, $this->user);
        $payload = $notification->toWebPush($this->user, $notification)->toArray();

        $this->assertSame($this->user->name.' przypisał(-a) Ci zadanie', $payload['body']);
        $this->assertSame(route('tasks.show', $task), $payload['data']['url']);
    }
}
