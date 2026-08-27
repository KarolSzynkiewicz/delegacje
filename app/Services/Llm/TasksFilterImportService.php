<?php

namespace App\Services\Llm;

use App\Exceptions\LlmException;
use App\Support\Llm\PromptContext;

/**
 * Parsuje wklejony tekst do propozycji zadań — JSON (jak Chrono) albo luźna lista
 * przez model. Domyślne pola z aktywnego filtra listy nakłada warstwa UI.
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
     * @param  array{category?: ?string, assignee_label?: ?string}  $filterDefaults
     * @return list<array{name: string, description: string, category: ?string, priority: ?int, subtasks: list<string>}>
     */
    public function parse(string $text, array $filterDefaults = [], int $maxTasks = 20): array
    {
        $text = trim($text);

        if ($text === '') {
            throw new LlmException('Wklej listę zadań albo JSON z tablicą tasks.');
        }

        try {
            $data = $this->decodeJson($text);
            $tasks = $this->normalizeTasks($data['tasks'] ?? $data, $maxTasks, $filterDefaults);

            if ($tasks !== []) {
                return $tasks;
            }
        } catch (LlmException) {
            // Luźny tekst — poniżej przez model albo linie.
        }

        $lineTasks = $this->fromPlainLines($text, $maxTasks, $filterDefaults);

        if ($lineTasks !== [] && ! $this->looksLikeProse($text)) {
            return $lineTasks;
        }

        if (! $this->isAvailable()) {
            if ($lineTasks !== []) {
                return $lineTasks;
            }

            throw LlmException::notConfigured();
        }

        return $this->suggestFromProse($text, $filterDefaults, $maxTasks);
    }

    /**
     * @param  array{category?: ?string, assignee_label?: ?string}  $filterDefaults
     * @return list<array{name: string, description: string, category: ?string, priority: ?int, subtasks: list<string>}>
     */
    protected function suggestFromProse(string $text, array $filterDefaults, int $maxTasks): array
    {
        $context = PromptContext::make()
            ->field('Tekst użytkownika', $text, 4000)
            ->field('Domyślna kategoria z filtra', $filterDefaults['category'] ?? null, 100)
            ->field('Kontekst przypisania z filtra', $filterDefaults['assignee_label'] ?? null, 100);

        $data = $this->askForJson(
            $context,
            implode(' ', [
                'Wyodrębnij zadania z tekstu użytkownika.',
                'Odpowiedz TYLKO JSON: {"tasks":[{"name":"…","description":"…","category":null,"priority":null,"subtasks":["…"]}]}.',
                'Po polsku, max '.$maxTasks.' zadań, name max 120 znaków.',
                'Jeśli w tekście jest lista — każde hasło to osobne zadanie.',
                'category i priority tylko gdy wynikają z tekstu; inaczej null.',
                'subtasks tylko gdy w tekście widać kroki.',
            ]),
            maxTokens: 2048,
        );

        $tasks = $this->normalizeTasks($data['tasks'] ?? $data, $maxTasks, $filterDefaults);

        if ($tasks === []) {
            throw new LlmException('Nie znaleziono zadań w wklejonym tekście.');
        }

        return $tasks;
    }

    /**
     * @param  array{category?: ?string}  $filterDefaults
     * @return list<array{name: string, description: string, category: ?string, priority: ?int, subtasks: list<string>}>
     */
    protected function fromPlainLines(string $text, int $maxTasks, array $filterDefaults): array
    {
        $lines = preg_split('/\R+/', $text) ?: [];
        $names = [];

        foreach ($lines as $line) {
            $name = $this->cleanLine($line, 120);

            if ($name === '' || str_starts_with($name, '{') || str_starts_with($name, '[')) {
                continue;
            }

            $names[] = $name;

            if (count($names) >= $maxTasks) {
                break;
            }
        }

        $defaultCategory = $this->nullableString($filterDefaults['category'] ?? null, 255);

        return array_map(fn (string $name) => [
            'name' => $name,
            'description' => '',
            'category' => $defaultCategory,
            'priority' => null,
            'subtasks' => [],
        ], $names);
    }

    protected function looksLikeProse(string $text): bool
    {
        $lines = array_values(array_filter(preg_split('/\R+/', $text) ?: [], fn ($l) => trim($l) !== ''));

        if (count($lines) <= 1 && mb_strlen($text) > 160) {
            return true;
        }

        $long = 0;
        foreach ($lines as $line) {
            if (mb_strlen(trim($line)) > 140) {
                $long++;
            }
        }

        return $long >= max(1, (int) floor(count($lines) / 2));
    }

    /**
     * @param  array{category?: ?string}  $filterDefaults
     * @return list<array{name: string, description: string, category: ?string, priority: ?int, subtasks: list<string>}>
     */
    protected function normalizeTasks(mixed $items, int $maxTasks, array $filterDefaults): array
    {
        if (! is_array($items)) {
            return [];
        }

        // Pojedyncze zadanie jako obiekt zamiast listy.
        if (isset($items['name']) && is_string($items['name'])) {
            $items = [$items];
        }

        $defaultCategory = $this->nullableString($filterDefaults['category'] ?? null, 255);
        $tasks = [];

        foreach ($items as $item) {
            if (is_string($item)) {
                $item = ['name' => $item];
            }

            if (! is_array($item)) {
                continue;
            }

            $name = $this->cleanLine($item['name'] ?? null, 120);

            if ($name === '') {
                continue;
            }

            $priority = isset($item['priority']) && is_numeric($item['priority'])
                ? max(1, min(5, (int) $item['priority']))
                : null;

            $category = $this->nullableString($item['category'] ?? null, 255) ?? $defaultCategory;

            $tasks[] = [
                'name' => $name,
                'description' => $this->cleanLine($item['description'] ?? null, 1000),
                'category' => $category,
                'priority' => $priority,
                'subtasks' => $this->stringList($item['subtasks'] ?? [], 12, 255),
            ];

            if (count($tasks) >= $maxTasks) {
                break;
            }
        }

        return $tasks;
    }

    protected function nullableString(mixed $value, int $maxLength): ?string
    {
        $value = $this->cleanLine($value, $maxLength);

        return $value === '' ? null : $value;
    }
}
