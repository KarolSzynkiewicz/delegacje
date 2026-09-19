<?php

namespace App\Notifications;

use App\Enums\NotificationEvent;
use App\Models\ProjectTask;
use App\Models\User;

class MeetingInvited extends ChronoNotification
{
    public function __construct(
        public readonly ProjectTask $meeting,
        public readonly User $invitedBy,
    ) {}

    public function event(): NotificationEvent
    {
        return NotificationEvent::MeetingInvited;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => $this->event()->value,
            'message' => $this->invitedBy->name.' umówił(-a) z Tobą spotkanie',
            'task_id' => $this->meeting->id,
            'task_name' => $this->meeting->name,
            'task_url' => route('tasks.show', $this->meeting),
            'context_name' => $this->meeting->meetingSlotLabel(),
            'excerpt' => $this->meeting->location,
            'assigned_by_id' => $this->invitedBy->id,
            'assigned_by_name' => $this->invitedBy->name,
        ];
    }
}
