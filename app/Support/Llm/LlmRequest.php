<?php

namespace App\Support\Llm;

/**
 * Zapytanie do modelu językowego, niezależne od dostawcy.
 *
 * Każdy provider tłumaczy ten obiekt na własny format HTTP.
 */
class LlmRequest
{
    /**
     * @param  string  $prompt  Treść zapytania użytkownika.
     * @param  string|null  $systemPrompt  Instrukcja systemowa (rola, styl, ograniczenia).
     * @param  string|null  $model  Nadpisanie modelu; null = model z konfiguracji dostawcy.
     * @param  float  $temperature  0.0 = deterministycznie, 1.0 = kreatywnie.
     * @param  int|null  $maxTokens  Limit tokenów odpowiedzi.
     */
    public function __construct(
        public readonly string $prompt,
        public readonly ?string $systemPrompt = null,
        public readonly ?string $model = null,
        public readonly float $temperature = 0.2,
        public readonly ?int $maxTokens = null,
    ) {}

    public function withModel(?string $model): self
    {
        return new self(
            prompt: $this->prompt,
            systemPrompt: $this->systemPrompt,
            model: $model,
            temperature: $this->temperature,
            maxTokens: $this->maxTokens,
        );
    }
}
