<?php

namespace App\Services\Llm;

use App\Contracts\Llm\LlmClient;
use App\Exceptions\LlmException;
use App\Models\ProjectTask;
use App\Support\Llm\LlmRequest;
use Illuminate\Support\Str;

/**
 * Proponuje podzadania dla istniejącego zadania — krótki prompt, odpowiedź JSON.
 */
class SubtaskSuggestionService
{
    public function __construct(
        private readonly LlmClient $llm,
    ) {}

    /**
     * @return list<string>
     */
    public function suggest(ProjectTask $task, int $maxItems = 8): array
    {
        if (! $this->llm->isConfigured()) {
            throw LlmException::notConfigured();
        }

        $task->loadMissing('subtasks');

        $lines = ['Zadanie: '.Str::limit(trim($task->name), 200, '…')];

        $description = trim($task->plainDescription());

        if ($description !== '') {
            $lines[] = 'Opis: '.Str::limit($description, 600, '…');
        }

        $existing = $task->subtasks
            ->pluck('name')
            ->map(fn (string $name) => Str::limit(trim($name), 120, '…'))
            ->filter()
            ->values()
            ->all();

        if ($existing !== []) {
            $lines[] = 'Istniejące podzadania (nie powtarzaj): '.implode('; ', $existing);
        }

        $response = $this->llm->generate(new LlmRequest(
            prompt: implode("\n", $lines),
            systemPrompt: 'Rozbij zadanie na krótkie podzadania. Odpowiedz TYLKO JSON: {"subtasks":["krok 1","krok 2"]}. Po polsku, max '.$maxItems.' pozycji, każda max 120 znaków, bez numeracji.',
            temperature: 0.2,
            maxTokens: 768,
            jsonMode: true,
        ));

        return $this->parseSubtasks($response->text, $maxItems);
    }

    /**
     * @return list<string>
     */
    private function parseSubtasks(string $text, int $maxItems): array
    {
        $text = trim($text);

        if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $text, $matches)) {
            $text = trim($matches[1]);
        }

        $data = json_decode($text, true);

        if (! is_array($data) && preg_match('/\{[\s\S]*\}/', $text, $jsonMatch)) {
            $data = json_decode($jsonMatch[0], true);
        }

        if (! is_array($data)) {
            $looksTruncated = ! str_ends_with($text, '}') && ! str_ends_with($text, ']');

            throw new LlmException(
                'Model zwrócił niepoprawny JSON z podzadaniami.'
                .($looksTruncated ? ' Odpowiedź wygląda na uciętą — spróbuj ponownie.' : '')
            );
        }

        $items = $data['subtasks'] ?? $data;

        if (! is_array($items)) {
            throw new LlmException('Model zwrócił JSON bez tablicy podzadań.');
        }

        $subtasks = [];

        foreach ($items as $item) {
            if (! is_string($item)) {
                continue;
            }

            $name = trim(preg_replace('/^\d+[\).\s-]+/', '', $item) ?? $item);

            if ($name === '') {
                continue;
            }

            $subtasks[] = Str::limit($name, 255, '');

            if (count($subtasks) >= $maxItems) {
                break;
            }
        }

        if ($subtasks === []) {
            throw new LlmException('Model nie zaproponował żadnych podzadań.');
        }

        return $subtasks;
    }
}
