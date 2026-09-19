<?php

namespace App\Listeners;

use App\Events\CommentPosted;
use App\Models\ProjectTask;
use App\Models\User;
use App\Notifications\CommentMentioned;
use App\Notifications\Notifier;
use App\Notifications\TaskCommentAdded;

class SendCommentNotifications
{
    public function handle(CommentPosted $event): void
    {
        $event->comment->loadMissing('commentable');

        foreach (array_unique($event->mentionUserIds) as $userId) {
            $user = User::query()->find((int) $userId);
            Notifier::send($user, new CommentMentioned($event->comment, $event->author), $event->author);
        }

        $commentable = $event->comment->commentable;
        if (! $commentable instanceof ProjectTask) {
            return;
        }

        $assigneeId = (int) $commentable->assigned_to;
        if ($assigneeId < 1 || in_array($assigneeId, $event->mentionUserIds, true)) {
            return;
        }

        $assignee = User::query()->find($assigneeId);
        Notifier::send(
            $assignee,
            new TaskCommentAdded($commentable, $event->comment, $event->author),
            $event->author,
        );
    }
}
