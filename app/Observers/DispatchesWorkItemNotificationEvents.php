<?php

namespace App\Observers;

use App\Enums\WorkItemStatus;
use App\Events\MeetingParticipantsChanged;
use App\Events\MentionWasCompleted;
use App\Events\TaskAssigneeChanged;
use App\Models\CommentMention;
use App\Models\ProjectTask;
use App\Models\TaskSubtask;
use App\Support\DomainActor;
use Illuminate\Database\Eloquent\Model;

class DispatchesWorkItemNotificationEvents
{
    public function created(Model $model): void
    {
        $this->dispatchAssigneeChanged($model, null);

        if ($model instanceof ProjectTask) {
            $this->dispatchMeetingParticipants($model, []);
        }
    }

    public function updated(Model $model): void
    {
        if ($model->wasChanged('assigned_to')) {
            $previous = $model->getOriginal('assigned_to');
            $this->dispatchAssigneeChanged($model, $previous !== null ? (int) $previous : null);
        }

        if ($model instanceof ProjectTask && $model->wasChanged('participant_ids')) {
            $this->dispatchMeetingParticipants($model, $this->originalParticipantIds($model));
        }

        if ($model instanceof CommentMention
            && $model->wasChanged('status')
            && $model->status === WorkItemStatus::Completed
        ) {
            MentionWasCompleted::dispatch($model, DomainActor::user());
        }
    }

    private function dispatchAssigneeChanged(Model $model, ?int $previousAssigneeId): void
    {
        if (! $model instanceof ProjectTask
            && ! $model instanceof TaskSubtask
            && ! $model instanceof CommentMention
        ) {
            return;
        }

        $assigneeId = (int) $model->assigned_to;
        if ($assigneeId < 1 || $assigneeId === (int) $previousAssigneeId) {
            return;
        }

        TaskAssigneeChanged::dispatch($model, $previousAssigneeId, DomainActor::user());
    }

    /**
     * @param  list<int>  $previousIds
     */
    private function dispatchMeetingParticipants(ProjectTask $task, array $previousIds): void
    {
        $added = array_values(array_diff($this->participantIds($task->participant_ids), $previousIds));
        if ($added === []) {
            return;
        }

        MeetingParticipantsChanged::dispatch($task, $added, DomainActor::user());
    }

    /**
     * @return list<int>
     */
    private function originalParticipantIds(ProjectTask $task): array
    {
        $original = $task->getOriginal('participant_ids');

        return $this->participantIds($original);
    }

    /**
     * @return list<int>
     */
    private function participantIds(mixed $value): array
    {
        if (is_string($value) && $value !== '') {
            $value = json_decode($value, true);
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $value))));
    }
}
