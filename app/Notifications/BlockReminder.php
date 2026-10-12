<?php

namespace App\Notifications;

use App\Enums\NotificationEvent;

class BlockReminder extends ChronoNotification
{
    public function __construct(
        public readonly string $title,
        public readonly string $when,
        public readonly string $url,
        public readonly bool $allDay,
    ) {}

    public function event(): NotificationEvent
    {
        return NotificationEvent::BlockReminder;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => $this->event()->value,
            'message' => $this->allDay
                ? 'Dziś w planie: '.$this->title
                : 'Za 15 minut: '.$this->title,
            'task_name' => $this->title,
            'task_url' => $this->url,
            'context_name' => $this->when,
        ];
    }
}
