<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TestPush extends Notification
{
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title((string) config('app.name', 'ChronoLogic'))
            ->body('To jest próbny push. Tak wygląda powiadomienie z tej strony.')
            ->icon(url('/favicon.svg'))
            ->tag('test-push')
            ->data(['url' => url('/profile')]);
    }
}
