<?php

namespace App\Support\Llm;

/**
 * Odpowiedź modelu w formacie wspólnym dla wszystkich dostawców.
 */
class LlmResponse
{
    /**
     * @param  array<string, mixed>  $raw  Surowa odpowiedź dostawcy (do debugowania).
     */
    public function __construct(
        public readonly string $text,
        public readonly string $provider,
        public readonly string $model,
        public readonly ?int $promptTokens = null,
        public readonly ?int $completionTokens = null,
        public readonly array $raw = [],
    ) {}

    public function totalTokens(): ?int
    {
        if ($this->promptTokens === null && $this->completionTokens === null) {
            return null;
        }

        return (int) $this->promptTokens + (int) $this->completionTokens;
    }
}
