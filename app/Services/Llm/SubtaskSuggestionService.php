<?php

namespace App\Services\Llm;

use App\Exceptions\LlmException;
use App\Models\ProjectTask;
use App\Support\Llm\PromptContext;

/**
 * Proponuje podzadania dla istniejącego zadania — krótki prompt, odpowiedź JSON.
 */
class SubtaskSuggestionService extends StructuredSuggestionService
{
    /**
     * @return list<string>
     */
    public function suggest(ProjectTask $task, int $maxItems = 8): array
    {
        $task->loadMissing('subtasks');

        $context = PromptContext::make()
            ->field('Zadanie', $task->name, 200)
            ->field('Opis', $task->plainDescription(), 600)
            ->list('Istniejące podzadania (nie powtarzaj)', $task->subtasks->pluck('name'));

        $data = $this->askForJson(
            $context,
            'Rozbij zadanie na krótkie podzadania. Odpowiedz TYLKO JSON: {"subtasks":["krok 1","krok 2"]}. Po polsku, max '.$maxItems.' pozycji, każda max 120 znaków, bez numeracji.',
            maxTokens: 768,
        );

        $items = $data['subtasks'] ?? $data;

        if (! is_array($items)) {
            throw new LlmException('Model zwrócił JSON bez tablicy podzadań.');
        }

        $subtasks = $this->stringList($items, $maxItems);

        if ($subtasks === []) {
            throw new LlmException('Model nie zaproponował żadnych podzadań.');
        }

        return $subtasks;
    }
}
