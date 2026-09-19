<?php

namespace App\Listeners;

use App\Events\MentionWasCompleted;
use App\Notifications\MentionCompleted;
use App\Notifications\Notifier;

class SendMentionCompletedNotification
{
    public function handle(MentionWasCompleted $event): void
    {
        $event->mention->loadMissing('createdBy');
        $creator = $event->mention->createdBy;
        if (! $creator) {
            return;
        }

        Notifier::send(
            $creator,
            new MentionCompleted($event->mention, $event->actor),
            $event->actor,
        );
    }
}
