<?php

namespace App\Livewire;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEvent;
use App\Enums\NotificationEventGroup;
use App\Models\NotificationPreference;
use App\Notifications\NotificationRouter;
use Livewire\Component;

class NotificationPreferences extends Component
{
    /**
     * @var array<string, bool>
     */
    public array $enabled = [];

    public function mount(): void
    {
        $router = app(NotificationRouter::class);
        $user = auth()->user();

        foreach (NotificationEvent::preferenceRows() as $event) {
            $this->enabled[$event->value] = $router->enabled(
                $user,
                $event,
                NotificationChannel::Database,
            );
        }
    }

    public function toggle(string $eventType): void
    {
        $event = NotificationEvent::tryFrom($eventType);
        if (! $event || ! $event->appearsInPreferences()) {
            return;
        }

        $next = ! ($this->enabled[$event->value] ?? $event->defaultEnabled(NotificationChannel::Database));
        $this->enabled[$event->value] = $next;

        NotificationPreference::query()->updateOrCreate(
            [
                'user_id' => auth()->id(),
                'event_type' => $event->value,
                'channel' => NotificationChannel::Database->value,
            ],
            ['enabled' => $next],
        );
    }

    public function render()
    {
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
        ]);
    }
}
