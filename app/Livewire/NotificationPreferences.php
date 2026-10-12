<?php

namespace App\Livewire;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEvent;
use App\Enums\NotificationEventGroup;
use App\Models\NotificationPreference;
use App\Notifications\NotificationRouter;
use App\Notifications\TestPush;
use Livewire\Component;

class NotificationPreferences extends Component
{
    /**
     * @var array<string, array<string, bool>>
     */
    public array $enabled = [];

    public ?string $testPush = null;

    public string $deadlineReminderTime = '09:00';

    public function mount(): void
    {
        $router = app(NotificationRouter::class);
        $user = auth()->user();
        $this->deadlineReminderTime = $user->reminderClock();

        foreach (NotificationEvent::preferenceRows() as $event) {
            foreach (NotificationChannel::preferenceColumns() as $channel) {
                $this->enabled[$event->value][$channel->value] = $router->enabled($user, $event, $channel);
            }
        }
    }

    public function toggle(string $eventType, string $channelValue): void
    {
        $event = NotificationEvent::tryFrom($eventType);
        $channel = $this->column($channelValue);

        if (! $event || ! $event->appearsInPreferences() || ! $channel) {
            return;
        }

        $next = ! ($this->enabled[$event->value][$channel->value] ?? false);
        $this->persist($event, $channel, $next);
    }

    public function toggleColumn(string $channelValue): void
    {
        $channel = $this->column($channelValue);

        if (! $channel) {
            return;
        }

        $rows = NotificationEvent::preferenceRows();
        $allOn = true;

        foreach ($rows as $event) {
            if (! ($this->enabled[$event->value][$channel->value] ?? false)) {
                $allOn = false;
                break;
            }
        }

        foreach ($rows as $event) {
            $this->persist($event, $channel, ! $allOn);
        }
    }

    public function sendTestPush(): void
    {
        $user = auth()->user();

        if ($user->pushSubscriptions()->doesntExist()) {
            $this->testPush = 'none';

            return;
        }

        try {
            $user->notifyNow(new TestPush);
            $this->testPush = 'sent';
        } catch (\Throwable $exception) {
            report($exception);
            $this->testPush = 'failed';
        }
    }

    public function storePushSubscription(array $subscription): void
    {
        $validated = validator($subscription, [
            'endpoint' => ['required', 'url', 'starts_with:https://', 'max:500'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'in:aes128gcm,aesgcm'],
        ])->validate();

        $stored = auth()->user()->updatePushSubscription(
            $validated['endpoint'],
            $validated['keys']['p256dh'],
            $validated['keys']['auth'],
            $validated['contentEncoding'] ?? 'aes128gcm',
        );

        $stored->forceFill([
            'user_agent' => substr((string) request()->userAgent(), 0, 512),
        ])->save();
    }

    public function updated(string $property, mixed $value): void
    {
        if (! preg_match('/^enabled\.([a-z0-9_]+)\.([a-z0-9_]+)$/', $property, $matches)) {
            return;
        }

        $event = NotificationEvent::tryFrom($matches[1]);
        $channel = $this->column($matches[2]);

        if (! $event || ! $event->appearsInPreferences() || ! $channel) {
            return;
        }

        $on = $value === true || $value === 1 || $value === '1';
        $this->enabled[$event->value][$channel->value] = $on;

        NotificationPreference::query()->updateOrCreate(
            [
                'user_id' => auth()->id(),
                'event_type' => $event->value,
                'channel' => $channel->value,
            ],
            ['enabled' => $on],
        );
    }

    public function updatedDeadlineReminderTime(mixed $value): void
    {
        $text = is_string($value) ? substr($value, 0, 5) : '';
        if (! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $text)) {
            $this->deadlineReminderTime = auth()->user()->reminderClock();

            return;
        }

        auth()->user()->forceFill(['deadline_reminder_time' => $text.':00'])->save();
        $this->deadlineReminderTime = $text;
    }

    public function deletePushSubscription(string $endpoint): void
    {
        if ($endpoint === '') {
            return;
        }

        auth()->user()->deletePushSubscription($endpoint);
    }

    public function render()
    {
        $channels = NotificationChannel::preferenceColumns();
        $columnStates = [];

        foreach ($channels as $channel) {
            $columnStates[$channel->value] = $this->columnState($channel);
        }

        $groups = [];

        foreach (NotificationEventGroup::cases() as $group) {
            $rows = array_values(array_filter(
                NotificationEvent::preferenceRows(),
                fn (NotificationEvent $event) => $event->group() === $group,
            ));

            if ($rows !== []) {
                $groups[] = ['group' => $group, 'events' => $rows];
            }
        }

        return view('livewire.notification-preferences', [
            'groups' => $groups,
            'channels' => $channels,
            'columnStates' => $columnStates,
            'vapidPublicKey' => (string) config('webpush.vapid.public_key'),
        ]);
    }

    private function column(string $channelValue): ?NotificationChannel
    {
        $channel = NotificationChannel::tryFrom($channelValue);

        if (! $channel || ! in_array($channel, NotificationChannel::preferenceColumns(), true)) {
            return null;
        }

        return $channel;
    }

    private function persist(NotificationEvent $event, NotificationChannel $channel, bool $enabled): void
    {
        $this->enabled[$event->value][$channel->value] = $enabled;

        NotificationPreference::query()->updateOrCreate(
            [
                'user_id' => auth()->id(),
                'event_type' => $event->value,
                'channel' => $channel->value,
            ],
            ['enabled' => $enabled],
        );
    }

    private function columnState(NotificationChannel $channel): string
    {
        $anyOn = false;
        $anyOff = false;

        foreach (NotificationEvent::preferenceRows() as $event) {
            if ($this->enabled[$event->value][$channel->value] ?? false) {
                $anyOn = true;
            } else {
                $anyOff = true;
            }
        }

        if ($anyOn && ! $anyOff) {
            return 'all';
        }

        if ($anyOn && $anyOff) {
            return 'some';
        }

        return 'none';
    }
}
