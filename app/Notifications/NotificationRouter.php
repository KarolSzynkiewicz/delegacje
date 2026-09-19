<?php

namespace App\Notifications;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEvent;
use App\Models\NotificationPreference;
use App\Models\User;

class NotificationRouter
{
    /**
     * @return list<string>
     */
    public function channels(object $notifiable, NotificationEvent $event): array
    {
        $channels = [];

        foreach (NotificationChannel::implemented() as $channel) {
            if ($this->enabled($notifiable, $event, $channel)) {
                $channels[] = $channel->laravelChannel();
            }
        }

        return $channels;
    }

    public function enabled(object $notifiable, NotificationEvent $event, NotificationChannel $channel): bool
    {
        if (! $notifiable instanceof User) {
            return $event->defaultEnabled($channel);
        }

        $stored = NotificationPreference::query()
            ->where('user_id', $notifiable->id)
            ->where('event_type', $event->value)
            ->where('channel', $channel->value)
            ->first();

        return $stored?->enabled ?? $event->defaultEnabled($channel);
    }
}
