<?php

namespace App\Contracts\Llm;

use App\Support\Llm\LlmRequest;
use App\Support\Llm\LlmResponse;

/**
 * Punkt wejścia dla całej aplikacji: „zapytaj model”.
 *
 * Reszta kodu (joby, Livewire, serwisy domenowe) zależy wyłącznie od tego
 * interfejsu i nie wie, który dostawca jest aktualnie skonfigurowany.
 */
interface LlmClient
{
    /**
     * @param  string|null  $provider  Wymuszenie dostawcy; null = aktywny z ustawień.
     *
     * @throws \App\Exceptions\LlmException
     */
    public function generate(LlmRequest $request, ?string $provider = null): LlmResponse;

    /**
     * Czy jakikolwiek dostawca ma zapisany klucz i da się go użyć.
     */
    public function isConfigured(): bool;
}
