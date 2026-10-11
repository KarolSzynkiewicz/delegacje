<?php

namespace Tests\Unit;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEvent;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\NotificationRouter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\TestCase;

class NotificationRouterTest extends TestCase
{
    use RefreshDatabase;

    public function test_falls_back_to_catalog_defaults(): void
    {
        $user = User::factory()->create();
        $router = new NotificationRouter;

        $this->assertTrue($router->enabled($user, NotificationEvent::TaskAssigned, NotificationChannel::Database));
        $this->assertTrue($router->enabled($user, NotificationEvent::TaskAssigned, NotificationChannel::Push));
        $this->assertFalse($router->enabled($user, NotificationEvent::CommentLiked, NotificationChannel::Push));
        $this->assertSame(['database', WebPushChannel::class], $router->channels($user, NotificationEvent::TaskAssigned));
        $this->assertSame(['database'], $router->channels($user, NotificationEvent::CommentLiked));
    }

    public function test_user_preference_overrides_default(): void
    {
        $user = User::factory()->create();
        NotificationPreference::query()->create([
            'user_id' => $user->id,
            'event_type' => NotificationEvent::CommentLiked->value,
            'channel' => NotificationChannel::Database->value,
            'enabled' => false,
        ]);

        $router = new NotificationRouter;

        $this->assertSame([], $router->channels($user, NotificationEvent::CommentLiked));
        $this->assertSame(['database', WebPushChannel::class], $router->channels($user, NotificationEvent::TaskAssigned));
    }

    public function test_mail_is_not_implemented_yet(): void
    {
        $user = User::factory()->create();
        $router = new NotificationRouter;

        $this->assertSame(['database', WebPushChannel::class], $router->channels($user, NotificationEvent::ApprovalRequested));
        $this->assertNotContains('mail', $router->channels($user, NotificationEvent::ApprovalRequested));
    }

    public function test_push_preference_can_turn_the_channel_off(): void
    {
        $user = User::factory()->create();
        NotificationPreference::query()->create([
            'user_id' => $user->id,
            'event_type' => NotificationEvent::MeetingInvited->value,
            'channel' => NotificationChannel::Push->value,
            'enabled' => false,
        ]);

        $router = new NotificationRouter;

        $this->assertSame(['database'], $router->channels($user, NotificationEvent::MeetingInvited));
    }
}
