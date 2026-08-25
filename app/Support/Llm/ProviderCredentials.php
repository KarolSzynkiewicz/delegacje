<?php

namespace App\Support\Llm;

/**
 * Poświadczenia jednego dostawcy LLM, oderwane od źródła (baza albo .env).
 */
class ProviderCredentials
{
    public function __construct(
        public readonly string $provider,
        public readonly string $apiKey,
        public readonly ?string $model = null,
        public readonly string $source = 'database',
    ) {}

    /**
     * Klucz w formie bezpiecznej do wyświetlenia w UI i logach.
     */
    public function maskedKey(): string
    {
        $length = mb_strlen($this->apiKey);

        if ($length <= 8) {
            return str_repeat('•', max($length, 4));
        }

        return mb_substr($this->apiKey, 0, 4).str_repeat('•', 8).mb_substr($this->apiKey, -4);
    }
}
