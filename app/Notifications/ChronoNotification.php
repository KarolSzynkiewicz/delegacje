<?php

namespace App\Notifications;

use App\Enums\NotificationEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

abstract class ChronoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    abstract public function event(): NotificationEvent;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return app(NotificationRouter::class)->channels($notifiable, $this->event());
    }
}
