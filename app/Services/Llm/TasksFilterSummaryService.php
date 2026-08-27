<?php

namespace App\Services\Llm;

use App\Exceptions\LlmException;
use App\Support\Llm\PromptContext;

/**
 * Podsumowuje widok listy zadań (aktualny filtr) — kontekst → JSON z narracją.
 */
class TasksFilterSummaryService extends StructuredSuggestionService
{
    /**
     * @param  list<string>  $filterLabels
     * @param  list<array<string, mixed>>  $tasks
     * @return array{headline: string, summary: string, highlights: list<string>, risks: list<string>}
     */
    public function summarize(array $filterLabels, array $tasks, int $totalCount): array
    {
        $context = PromptContext::make()
            ->list('Aktywne filtry', $filterLabels, 160, 20)
            ->field('Liczba zadań w filtrze', (string) $totalCount, 20)
            ->records('Próbka zadań', $tasks, 40, [
                'id', 'name', 'status', 'category', 'assignee', 'priority', 'due_date', 'type',
            ]);

        $data = $this->askForJson(
            $context,
            implode(' ', [
                'Jesteś asystentem backlogu ChronoLogic.',
                'Na podstawie filtrów i próbki zadań napisz zwięzłe podsumowanie widoku.',
                'Odpowiedz TYLKO JSON:',
                '{"headline":"jedno zdanie","summary":"2-4 zdania prozą","highlights":["punkt"],"risks":["ryzyko"]}.',
                'Po polsku. highlights max 5, risks max 4. Nazwy zadań cytuj dosłownie gdy ważne.',
                'Nie wymyślaj zadań spoza próbki. Jeśli lista pusta — napisz, że filtr nic nie zwraca.',
            ]),
            maxTokens: 1024,
        );

        $headline = $this->cleanLine($data['headline'] ?? null, 160);
        $summary = $this->cleanLine($data['summary'] ?? null, 800);
        $highlights = $this->stringList($data['highlights'] ?? [], 5, 200);
        $risks = $this->stringList($data['risks'] ?? [], 4, 200);

        if ($headline === '' && $summary === '') {
            throw new LlmException('Model nie zwrócił podsumowania widoku.');
        }

        return [
            'headline' => $headline !== '' ? $headline : 'Podsumowanie widoku',
            'summary' => $summary,
            'highlights' => $highlights,
            'risks' => $risks,
        ];
    }
}
