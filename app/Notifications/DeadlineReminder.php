<?php

namespace App\Notifications;

use App\Enums\NotificationEvent;

class DeadlineReminder extends ChronoNotification
{
    public function __construct(
        public readonly string $title,
        public readonly string $url,
    ) {}

    public function event(): NotificationEvent
    {
        return NotificationEvent::DeadlineReminder;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => $this->event()->value,
            'message' => 'Dziś mija termin: '.$this->title,
            'task_name' => $this->title,
            'task_url' => $this->url,
        ];
    }
}
