<?php

namespace App\Mcp\Support;

use App\Enums\TaskStatus;
use App\Models\ProjectTask;

class CategoryDictionary
{
    /**
     * Słownik kategorii używanych w systemie, od najczęściej otwartych.
     *
     * @return array<int, array{category: string, open_tasks: int, tasks: int}>
     */
    public static function all(?string $q = null): array
    {
        $query = ProjectTask::query()
            ->select('category')
            ->selectRaw('COUNT(*) as tasks')
            ->selectRaw(
                'SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as open_tasks',
                [TaskStatus::PENDING->value, TaskStatus::IN_PROGRESS->value],
            )
            ->whereNotNull('category')
            ->where('category', '!=', '');

        if ($q !== null && $q !== '') {
            $query->where('category', 'like', '%'.$q.'%');
        }

        return $query
            ->groupBy('category')
            ->orderByDesc('open_tasks')
            ->orderByDesc('tasks')
            ->orderBy('category')
            ->get()
            ->map(fn ($row) => [
                'category' => (string) $row->category,
                'open_tasks' => (int) $row->open_tasks,
                'tasks' => (int) $row->tasks,
            ])
            ->all();
    }
}
