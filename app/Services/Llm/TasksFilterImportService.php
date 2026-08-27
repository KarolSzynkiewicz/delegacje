<?php

namespace App\Services\Llm;

use App\Exceptions\LlmException;
use App\Services\UserMentionService;

/**
 * Import Impka: tylko nowe rekordy (JSON albo lista linii).
 * Bez LLM i bez update po id — edycja istniejących to domena Ediego.
 */
class TasksFilterImportService extends StructuredSuggestionService
{
    /**
     * @return array{name: string, description: string, category: string|null, priority: int|null, subtasks: list<string>}
     */
    public static function importFormatExample(): array
    {
        return [
            'tasks' => [
                [
                    'name' => 'Nazwa zadania',
                    'description' => 'Opcjonalny opis',
                    'category' => 'AI / Sprint',
                    'priority' => 2,
                    'subtasks' => ['Krok 1', 'Krok 2'],
                ],
            ],
        ];
    }

    /**
     * @param  array{category?: ?string, assigned_to?: ?int, assignee_label?: ?string}  $filterDefaults
     * @return list<array<string, mixed>>
     */
    public function parseJson(string $text, array $filterDefaults = [], int $maxTasks = 200): array
    {
        $text = trim($text);

        if ($text === '') {
            throw new LlmException('Wklej JSON z tablicą tasks.');
        }

        $data = $this->decodeJson($text);
        $items = $data['tasks'] ?? $data;

        if (! is_array($items)) {
            throw new LlmException('JSON musi zawierać tablicę tasks.');
        }

        if (isset($items['name']) && is_string($items['name'])) {
            $items = [$items];
        }

        $proposals = [];

        foreach ($items as $item) {
            if (is_string($item)) {
                $item = ['name' => $item];
            }

            if (! is_array($item)) {
                continue;
            }

            $proposal = $this->proposalFromJsonItem($item, $filterDefaults);

            if ($proposal === null) {
                continue;
            }

            $proposals[] = $proposal;

            if (count($proposals) >= $maxTasks) {
                break;
            }
        }

        if ($proposals === []) {
            throw new LlmException('Nie znaleziono zadań w JSON.');
        }

        return $proposals;
    }

    /**
     * @param  array{category?: ?string, assigned_to?: ?int, assignee_label?: ?string}  $filterDefaults
     * @return list<array<string, mixed>>
     */
    public function parseLines(string $text, array $filterDefaults = [], int $maxTasks = 50): array
    {
        $text = trim($text);

        if ($text === '') {
            throw new LlmException('Wklej listę linii (jedna linia = jedno zadanie).');
        }

        $lines = preg_split('/\R+/', $text) ?: [];
        $proposals = [];

        foreach ($lines as $line) {
            $proposal = $this->proposalFromLine((string) $line, $filterDefaults);

            if ($proposal === null) {
                continue;
            }

            $proposals[] = $proposal;

            if (count($proposals) >= $maxTasks) {
                break;
            }
        }

        if ($proposals === []) {
            throw new LlmException('Nie znaleziono zadań w liście linii.');
        }

        return $proposals;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array{category?: ?string, assigned_to?: ?int, assignee_label?: ?string}  $filterDefaults
     * @return array<string, mixed>|null
     */
    protected function proposalFromJsonItem(array $item, array $filterDefaults): ?array
    {
        $name = $this->cleanLine($item['name'] ?? null, 120);

        if ($name === '') {
            return null;
        }

        $assignedTo = isset($item['assigned_to']) && is_numeric($item['assigned_to'])
            ? (int) $item['assigned_to']
            : ($filterDefaults['assigned_to'] ?? null);

        $priority = isset($item['priority']) && is_numeric($item['priority'])
            ? max(1, min(5, (int) $item['priority']))
            : null;

        $category = $this->nullableString($item['category'] ?? null, 255)
            ?? $this->nullableString($filterDefaults['category'] ?? null, 255);

        $subtasks = $item['subtasks'] ?? [];
        if (! is_array($subtasks)) {
            $subtasks = [];
        }

        return [
            'action' => 'create',
            'existing_task_id' => null,
            'name' => $name,
            'description' => $this->cleanLine($item['description'] ?? null, 4000),
            'category' => $category,
            'priority' => $priority,
            'due_date' => $this->nullableString($item['due_date'] ?? null, 32),
            'status' => $this->nullableString($item['status'] ?? null, 32),
            'assigned_to' => $assignedTo,
            'assignee' => is_string($item['assignee'] ?? null) ? $item['assignee'] : ($filterDefaults['assignee_label'] ?? null),
            'sprint_id' => isset($item['sprint_id']) && is_numeric($item['sprint_id'])
                ? (int) $item['sprint_id']
                : ($filterDefaults['sprint_id'] ?? null),
            'subtasks' => $subtasks,
        ];
    }

    /**
     * @param  array{category?: ?string, assigned_to?: ?int, assignee_label?: ?string}  $filterDefaults
     * @return array<string, mixed>|null
     */
    protected function proposalFromLine(string $line, array $filterDefaults): ?array
    {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '{') || str_starts_with($line, '[')) {
            return null;
        }

        $fields = UserMentionService::approvalFieldsFromBody($line, '');
        $title = trim(preg_replace('/[\s\-–—]+$/u', '', $fields['title']) ?? $fields['title']);

        if ($title === '') {
            return null;
        }

        $assignedTo = $filterDefaults['assigned_to'] ?? null;
        $assigneeLabel = $filterDefaults['assignee_label'] ?? null;

        foreach (UserMentionService::extractHandles($line) as $handle) {
            $user = UserMentionService::resolveUserByMentionHandle($handle);

            if ($user) {
                $assignedTo = $user->id;
                $assigneeLabel = $user->name;
                break;
            }
        }

        return [
            'action' => 'create',
            'existing_task_id' => null,
            'name' => mb_substr($title, 0, 120),
            'description' => $fields['description'] ?? '',
            'category' => $this->nullableString($filterDefaults['category'] ?? null, 255),
            'priority' => null,
            'due_date' => null,
            'status' => null,
            'assigned_to' => $assignedTo,
            'assignee' => $assigneeLabel,
            'sprint_id' => $filterDefaults['sprint_id'] ?? null,
            'subtasks' => [],
        ];
    }

    protected function nullableString(mixed $value, int $maxLength): ?string
    {
        $value = $this->cleanLine($value, $maxLength);

        return $value === '' ? null : $value;
    }
}
