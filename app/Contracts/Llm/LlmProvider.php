<?php

namespace App\Contracts\Llm;

use App\Support\Llm\LlmRequest;
use App\Support\Llm\LlmResponse;
use App\Support\Llm\ProviderCredentials;

/**
 * Adapter jednego dostawcy LLM (Gemini, OpenAI, …).
 *
 * Implementacja zna tylko HTTP API swojego dostawcy — nie wie nic o tym,
 * skąd wziął się klucz ani kto go woła.
 */
interface LlmProvider
{
    /**
     * Klucz dostawcy zgodny z config/llm.php, np. „gemini”.
     */
    public function key(): string;

    /**
     * Nazwa do wyświetlenia w UI.
     */
    public function label(): string;

    /**
     * Modele sugerowane w interfejsie.
     *
     * @return array<string, string> model => etykieta
     */
    public function availableModels(): array;

    public function defaultModel(): string;

    /**
     * Wykonaj zapytanie do modelu.
     *
     * @throws \App\Exceptions\LlmException
     */
    public function generate(LlmRequest $request, ProviderCredentials $credentials): LlmResponse;
}
