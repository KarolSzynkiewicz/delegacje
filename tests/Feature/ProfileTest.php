<?php

namespace Tests\Feature;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEvent;
use App\Livewire\NotificationPreferences;
use App\Models\User;
use App\Notifications\TestPush;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertSoftDeleted($user);
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_profile_page_uses_the_polish_layout(): void
    {
        $user = User::factory()->create(['name' => 'Anna Nowak']);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Dane użytkownika')
            ->assertSee('Zmień hasło')
            ->assertSee('Zapisz zmiany')
            ->assertSee('Zapisz hasło')
            ->assertSee('Usuń konto')
            ->assertSee('Sesje i urządzenia')
            ->assertSee('ta sesja')
            ->assertSee('Kierownik · nie zarządzasz żadnymi projektami')
            ->assertSee('W aplikacji')
            ->assertSee('Żadna przeglądarka nie dostaje jeszcze push.')
            ->assertDontSee('Delete Account')
            ->assertDontSee('Profile Information')
            ->assertDontSee('Update Password');
    }

    public function test_revoking_a_session_leaves_the_current_one_and_other_users(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $raw = str_repeat('c', 40);
        $current = hash('sha256', $raw);
        $other = hash('sha256', 'other-browser-session');
        $foreign = hash('sha256', 'someone-elses-session');
        $now = time();

        DB::table('sessions')->insert([
            $this->sessionRow($other, $user->id, $now),
            $this->sessionRow($foreign, $otherUser->id, $now),
        ]);

        config(['session.driver' => 'database']);

        $this->actingAs($user)
            ->withCookie((string) config('session.cookie'), $raw)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Chrome · Windows')
            ->assertSee('Wyloguj');

        $this->delete('/profile/sessions/'.$current)->assertRedirect('/profile');
        $this->assertDatabaseHas('sessions', ['id' => $current]);

        $this->delete('/profile/sessions/'.$other)->assertRedirect('/profile');
        $this->assertDatabaseMissing('sessions', ['id' => $other]);
        $this->assertDatabaseHas('sessions', ['id' => $foreign]);

        DB::table('sessions')->insert($this->sessionRow($other, $user->id, $now));

        $this->delete('/profile/sessions')->assertRedirect('/profile');
        $this->assertDatabaseMissing('sessions', ['id' => $other]);
        $this->assertDatabaseHas('sessions', ['id' => $current]);
        $this->assertDatabaseHas('sessions', ['id' => $foreign]);
    }

    public function test_detaching_a_push_device_is_limited_to_the_owner(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $agent = 'Mozilla/5.0 (Windows NT 10.0) Chrome/120.0.0.0';

        $own = $user->updatePushSubscription('https://push.example.test/own', 'public-key', 'auth-token', 'aes128gcm');
        $own->forceFill(['user_agent' => $agent])->save();
        $theirs = $otherUser->updatePushSubscription('https://push.example.test/theirs', 'public-key', 'auth-token', 'aes128gcm');

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Chrome · Windows')
            ->assertSee('Odłącz');

        $this->delete(route('profile.devices.destroy', $theirs->id))->assertRedirect('/profile');
        $this->assertDatabaseHas('push_subscriptions', ['id' => $theirs->id]);

        $this->delete(route('profile.devices.destroy', $own->id))->assertRedirect('/profile');
        $this->assertDatabaseMissing('push_subscriptions', ['id' => $own->id]);
    }

    public function test_preference_checkbox_persists_as_soon_as_the_model_changes(): void
    {
        $user = User::factory()->create();
        $push = NotificationChannel::Push->value;
        $liked = NotificationEvent::CommentLiked->value;

        $this->actingAs($user);

        Livewire::test(NotificationPreferences::class)
            ->set('enabled.'.$liked.'.'.$push, true)
            ->assertSet('enabled.'.$liked.'.'.$push, true)
            ->call('storePushSubscription', [
                'endpoint' => 'https://push.example.test/profile-agent',
                'keys' => ['p256dh' => 'public-key', 'auth' => 'auth-token'],
                'contentEncoding' => 'aes128gcm',
            ]);

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $user->id,
            'event_type' => $liked,
            'channel' => $push,
            'enabled' => 1,
        ]);

        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_id' => $user->id,
            'endpoint' => 'https://push.example.test/profile-agent',
            'user_agent' => 'Symfony',
        ]);
    }

    public function test_profile_test_push_goes_only_to_a_saved_device(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(NotificationPreferences::class)
            ->call('sendTestPush')
            ->assertSet('testPush', 'none')
            ->assertSee('Wyślij próbę');

        Notification::fake();

        $user->updatePushSubscription('https://push.example.test/probe', 'public-key', 'auth-token', 'aes128gcm');

        Livewire::test(NotificationPreferences::class)
            ->call('sendTestPush')
            ->assertSet('testPush', 'sent');

        Notification::assertSentTo($user, TestPush::class, function (TestPush $notification) use ($user) {
            $payload = $notification->toWebPush($user, $notification)->toArray();

            return $payload['title'] === config('app.name')
                && str_contains($payload['body'], 'próbny push')
                && $payload['data']['url'] === url('/profile')
                && $notification->via($user) === [\NotificationChannels\WebPush\WebPushChannel::class];
        });
    }

    /**
     * @return array{id: string, user_id: int, ip_address: string, user_agent: string, payload: string, last_activity: int}
     */
    private function sessionRow(string $id, int $userId, int $seen): array
    {
        return [
            'id' => $id,
            'user_id' => $userId,
            'ip_address' => '10.0.0.8',
            'user_agent' => 'Mozilla/5.0 Chrome/120.0 Windows',
            'payload' => serialize([]),
            'last_activity' => $seen,
        ];
    }
}
