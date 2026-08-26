<?php

namespace App\Services\Llm;

use App\Contracts\Llm\LlmClient;
use App\Exceptions\LlmException;
use App\Support\Llm\LlmRequest;
use App\Support\Llm\PromptContext;
use Illuminate\Support\Str;

/**
 * Baza dla serwisów typu AskChrono: kontekst + instrukcja formatu → JSON → tablica PHP.
 *
 * Model nie ma tu żadnych narzędzi ani dostępu do bazy — dostaje wyłącznie tekst
 * zbudowany przez aplikację i zwraca tekst, który sami parsujemy i walidujemy.
 * Zapis czegokolwiek jest zawsze decyzją warstwy wyżej (człowiek w pętli).
 */
abstract class StructuredSuggestionService
{
    public function __construct(
        protected readonly LlmClient $llm,
    ) {}

    public function isAvailable(): bool
    {
        return $this->llm->isConfigured();
    }

    /**
     * Wysyła kontekst z instrukcją formatu i zwraca zdekodowany JSON.
     *
     * @return array<array-key, mixed>
     *
     * @throws LlmException gdy brak dostawcy albo odpowiedź nie jest poprawnym JSON-em
     */
    protected function askForJson(
        PromptContext|string $context,
        string $systemPrompt,
        int $maxTokens = 768,
        float $temperature = 0.2,
    ): array {
        if (! $this->llm->isConfigured()) {
            throw LlmException::notConfigured();
        }

        $response = $this->llm->generate(new LlmRequest(
            prompt: (string) $context,
            systemPrompt: $systemPrompt,
            temperature: $temperature,
            maxTokens: $maxTokens,
            jsonMode: true,
        ));

        return $this->decodeJson($response->text);
    }

    /**
     * @return array<array-key, mixed>
     *
     * @throws LlmException
     */
    protected function decodeJson(string $text): array
    {
        $text = trim($text);

        if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $text, $matches)) {
            $text = trim($matches[1]);
        }

        $data = json_decode($text, true);

        if (! is_array($data) && preg_match('/[\{\[][\s\S]*[\}\]]/', $text, $jsonMatch)) {
            $data = json_decode($jsonMatch[0], true);
        }

        if (! is_array($data)) {
            $looksTruncated = ! str_ends_with($text, '}') && ! str_ends_with($text, ']');

            throw new LlmException(
                'Model zwrócił niepoprawny JSON.'
                .($looksTruncated ? ' Odpowiedź wygląda na uciętą — spróbuj ponownie.' : '')
            );
        }

        return $data;
    }

    /**
     * Wyciąga listę stringów: zdejmuje numerację, przycina i odrzuca puste.
     *
     * @return list<string>
     */
    protected function stringList(mixed $items, int $maxItems, int $maxLength = 255): array
    {
        if (! is_array($items)) {
            return [];
        }

        $values = [];

        foreach ($items as $item) {
            if (! is_string($item)) {
                continue;
            }

            $value = $this->cleanLine($item, $maxLength);

            if ($value === '') {
                continue;
            }

            $values[] = $value;

            if (count($values) >= $maxItems) {
                break;
            }
        }

        return $values;
    }

    protected function cleanLine(mixed $value, int $maxLength): string
    {
        if (! is_string($value)) {
            return '';
        }

        $value = trim(preg_replace('/^\d+[\).\s-]+/', '', $value) ?? $value);

        return $value === '' ? '' : Str::limit($value, $maxLength, '');
    }
}
