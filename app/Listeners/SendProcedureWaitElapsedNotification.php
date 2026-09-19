<?php

namespace App\Listeners;

use App\Events\ProcedureWaitFinished;
use App\Models\User;
use App\Notifications\Notifier;
use App\Notifications\ProcedureWaitElapsed;

class SendProcedureWaitElapsedNotification
{
    public function handle(ProcedureWaitFinished $event): void
    {
        $event->run->loadMissing(['task', 'startedBy', 'version']);
        $node = $event->run->findNodeById($event->step->node_id);
        $ids = array_filter([
            (int) ($node['assigned_user_id'] ?? 0),
            (int) ($event->run->task?->assigned_to ?? 0),
        ]);

        $actor = $event->run->startedBy;

        foreach (array_unique($ids) as $userId) {
            if ($userId <= 0) {
                continue;
            }

            $user = User::query()->find($userId);
            Notifier::send(
                $user,
                new ProcedureWaitElapsed($event->run, $event->step, $actor),
            );
        }
    }
}
