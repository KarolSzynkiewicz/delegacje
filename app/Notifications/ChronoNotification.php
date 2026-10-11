<?php

namespace App\Notifications;

use App\Enums\NotificationEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

abstract class ChronoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    abstract public function event(): NotificationEvent;

    /**
     * @return array<string, mixed>
     */
    abstract public function toDatabase(object $notifiable): array;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return app(NotificationRouter::class)->channels($notifiable, $this->event());
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        $data = $this->toDatabase($notifiable);
        $url = $data['resource_url'] ?? $data['task_url'] ?? $data['url'] ?? url('/notifications');

        return (new WebPushMessage)
            ->title((string) config('app.name', 'ChronoLogic'))
            ->body((string) ($data['message'] ?? 'Nowe powiadomienie'))
            ->icon(url('/favicon.svg'))
            ->tag($this->event()->value.($notification->id ? '-'.$notification->id : ''))
            ->data(['url' => $url]);
    }
}
