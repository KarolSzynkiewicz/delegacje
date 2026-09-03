<?php

namespace App\Listeners;

use App\Events\ProcedureRunStepEntered;
use App\Models\CommentMention;
use App\Models\User;
use App\Services\UserMentionService;
use Illuminate\Support\Facades\Auth;

class NotifyProcedureStepAssignee
{
    public function __construct(private UserMentionService $mentions) {}

    public function handle(ProcedureRunStepEntered $event): void
    {
        $node = $event->node;
        $type = $node['type'] ?? '';

        if (in_array($type, ['start', 'end', 'note', 'approval'], true)) {
            return;
        }

        $assigneeId = (int) ($node['assigned_user_id'] ?? 0);
        if ($assigneeId <= 0) {
            return;
        }

        $previousAssigneeId = (int) ($event->previousNode['assigned_user_id'] ?? 0);
        if ($previousAssigneeId === $assigneeId) {
            return;
        }

        $event->run->loadMissing('task');
        $task = $event->run->task;
        if (! $task) {
            return;
        }

        if ((int) $task->assigned_to === $assigneeId) {
            return;
        }

        $assignee = User::query()->find($assigneeId);
        if (! $assignee) {
            return;
        }

        $author = Auth::user();
        if (! $author) {
            $task->loadMissing('createdBy');
            $author = $task->createdBy;
        }
        if (! $author) {
            return;
        }

        $stepName = $this->stepName($node);
        $body = '@'.$assignee->name.'! Krok: '.$stepName."\nZrób: ".$this->whatToDo($node);
        $comment = $task->addComment($body, $author);
        $this->mentions->notifyCommentMentions($comment, $author);

        $mentions = CommentMention::query()
            ->where('comment_id', $comment->id)
            ->get();

        foreach ($mentions as $mention) {
            $mention->update(['title' => $stepName]);
        }

        $ids = $mentions->pluck('id')->all();
        if ($ids === []) {
            return;
        }

        $variables = $event->run->variables ?? [];
        $key = (string) $assigneeId;
        $variables['step_mentions'][$key] = array_values(array_unique(array_merge(
            $variables['step_mentions'][$key] ?? [],
            $ids
        )));
        $event->run->update(['variables' => $variables]);
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function stepName(array $node): string
    {
        $name = trim((string) ($node['name'] ?? ''));

        return $name !== '' ? $name : 'krok procedury';
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function whatToDo(array $node): string
    {
        $instructions = trim((string) ($node['instructions'] ?? ''));
        if ($instructions !== '') {
            return $instructions;
        }

        $description = trim((string) ($node['description'] ?? ''));
        if ($description !== '') {
            return $description;
        }

        if (($node['type'] ?? '') === 'checklist') {
            $titles = collect($node['checklist'] ?? [])
                ->map(fn ($item) => trim((string) ($item['title'] ?? '')))
                ->filter()
                ->implode(', ');
            if ($titles !== '') {
                return $titles;
            }
        }

        return 'Wykonaj ten krok w procedurze.';
    }
}
