<?php

namespace App\Listeners;

use App\Events\MeetingParticipantsChanged;
use App\Models\User;
use App\Notifications\MeetingInvited;
use App\Notifications\Notifier;

class SendMeetingInvitedNotification
{
    public function handle(MeetingParticipantsChanged $event): void
    {
        $actor = $event->actor;
        if (! $actor || $event->addedUserIds === []) {
            return;
        }

        $assigneeId = (int) $event->meeting->assigned_to;

        foreach (array_unique($event->addedUserIds) as $userId) {
            if ($userId === $assigneeId) {
                continue;
            }

            $user = User::query()->find($userId);
            Notifier::send($user, new MeetingInvited($event->meeting, $actor), $actor);
        }
    }
}
