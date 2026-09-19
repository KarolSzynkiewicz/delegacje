<?php

namespace App\Notifications;

use App\Models\User;

final class Notifier
{
    public static function send(?User $notifiable, ChronoNotification $notification, ?User $actor = null): void
    {
        if (! $notifiable) {
            return;
        }

        if ($actor && (int) $notifiable->id === (int) $actor->id) {
            return;
        }

        if ($notification->via($notifiable) === []) {
            return;
        }

        $notifiable->notify($notification);
    }
}
