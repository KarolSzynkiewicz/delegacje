<?php

namespace App\Services;

use App\Models\ProjectTask;
use App\Models\Sprint;
use App\WorkItems\ProjectTaskFields;

class SprintAssignmentService
{
    /**
     * @param  array<int, int>  $taskIds
     * @return array{assigned: array<int, array<string, mixed>>, skipped: array<int, array<string, mixed>>, not_found: array<int, int>}
     */
    public function assign(int $sprintId, array $taskIds): array
    {
        if (! Sprint::query()->where('id', $sprintId)->exists()) {
            throw new \InvalidArgumentException("Sprint o ID {$sprintId} nie istnieje.");
        }

        $fields = app(ProjectTaskFields::class);
        $assigned = [];
        $skipped = [];
        $notFound = [];

        foreach (array_unique($taskIds) as $taskId) {
            $task = ProjectTask::query()->find($taskId);

            if (! $task) {
                $notFound[] = (int) $taskId;

                continue;
            }

            if ((int) $task->sprint_id === $sprintId) {
                $skipped[] = [
                    'task_id' => $task->id,
                    'reason' => 'Zadanie jest już w tym sprincie.',
                    'name' => $task->name,
                ];

                continue;
            }

            $fields->writeSprint($task, (string) $sprintId);
            $task->refresh();

            $assigned[] = [
                'task_id' => $task->id,
                'name' => $task->name,
                'sprint_id' => $task->sprint_id,
                'sprint_position' => $task->sprint_position,
            ];
        }

        return compact('assigned', 'skipped', 'notFound');
    }
}
