<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\ProjectTask;
use App\Models\TaskSubtask;
use App\Models\TaskSubtaskEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TaskCreationService
{
    /**
     * @param  array{
     *     name: string,
     *     description?: string|null,
     *     category?: string|null,
     *     priority?: int|null,
     *     due_date?: string|null,
     *     assigned_to?: int|null,
     *     sprint_id?: int|null,
     *     subtasks?: array<int, string>
     * }  $data
     */
    public function create(array $data, User $creator): ProjectTask
    {
        return DB::transaction(function () use ($data, $creator) {
            $sprintId = $data['sprint_id'] ?? null;

            $task = ProjectTask::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => TaskStatus::PENDING,
                'category' => $data['category'] ?? null,
                'priority' => $data['priority'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'assigned_to' => $data['assigned_to'] ?? null,
                'sprint_id' => $sprintId,
                'sprint_position' => $sprintId
                    ? (int) ProjectTask::query()->where('sprint_id', $sprintId)->max('sprint_position') + 1
                    : null,
                'created_by' => $creator->id,
            ]);

            $subtasks = $data['subtasks'] ?? [];

            foreach (array_values($subtasks) as $index => $name) {
                $name = trim((string) $name);

                if ($name === '') {
                    continue;
                }

                $subtask = TaskSubtask::create([
                    'task_id' => $task->id,
                    'sort_order' => $index + 1,
                    'name' => $name,
                    'is_completed' => false,
                    'created_by' => $creator->id,
                ]);

                TaskSubtaskEvent::log($subtask, 'created', $creator->id);
            }

            return $task->fresh(['subtasks']);
        });
    }
}
