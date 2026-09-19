<?php

namespace App\Notifications;

use App\Enums\NotificationEvent;
use App\Models\CommentMention;
use App\Models\User;

class MentionCompleted extends ChronoNotification
{
    public function __construct(
        public readonly CommentMention $mention,
        public readonly ?User $completedBy,
    ) {}

    public function event(): NotificationEvent
    {
        return NotificationEvent::MentionCompleted;
    }

    public function toDatabase(object $notifiable): array
    {
        $this->mention->loadMissing('comment');
        $who = $this->completedBy?->name ?? 'Ktoś';

        return [
            'type' => 'mention_completed',
            'message' => $who.' ukończył(-a) wzmiankę, którą zleciłeś(-aś)',
            'task_name' => $this->mention->title,
            'task_url' => $this->mention->comment?->urlWithCommentAnchor() ?? url('/tasks2'),
            'excerpt' => $this->mention->comment?->bodyExcerpt(),
        ];
    }
}
