<?php

namespace App\Services\Llm;

use App\Contracts\Llm\LlmClient;
use App\Contracts\Llm\LlmCredentialRepository;
use App\Contracts\Llm\LlmProvider;
use App\Exceptions\LlmException;
use App\Support\Llm\LlmRequest;
use App\Support\Llm\LlmResponse;

/**
 * Handler stojący między aplikacją a dostawcami LLM.
 *
 * Bierze poświadczenia z repozytorium, buduje adapter wskazany w
 * config/llm.php i deleguje żądanie. Kod wołający zna tylko LlmClient,
 * więc podmiana Gemini na cokolwiek innego to zmiana ustawień, nie kodu.
 */
class LlmManager implements LlmClient
{
    public function __construct(
        private readonly LlmCredentialRepository $credentials,
    ) {}

    public function generate(LlmRequest $request, ?string $provider = null): LlmResponse
    {
        $providerKey = $provider ?? $this->credentials->activeProvider();

        if (blank($providerKey)) {
            throw LlmException::notConfigured();
        }

        $credentials = $this->credentials->find($providerKey);

        if (! $credentials) {
            throw LlmException::missingKey($providerKey);
        }

        $response = $this->provider($providerKey)->generate($request, $credentials);

        $this->credentials->touchLastUsed($providerKey);

        return $response;
    }

    public function isConfigured(): bool
    {
        return $this->credentials->activeProvider() !== null;
    }

    /**
     * Zbuduj adapter dostawcy na podstawie rejestru w config/llm.php.
     */
    public function provider(string $key): LlmProvider
    {
        $config = config("llm.providers.{$key}");

        if (! is_array($config) || ! isset($config['driver'])) {
            throw LlmException::unknownProvider($key);
        }

        $driver = $config['driver'];

        if (! is_a($driver, LlmProvider::class, true)) {
            throw LlmException::unknownProvider($key);
        }

        return new $driver($key, $config);
    }

    /**
     * Wszyscy zarejestrowani dostawcy — do listy wyboru w UI.
     *
     * @return array<string, LlmProvider>
     */
    public function providers(): array
    {
        $providers = [];

        foreach (array_keys((array) config('llm.providers', [])) as $key) {
            $providers[$key] = $this->provider($key);
        }

        return $providers;
    }

    /**
     * Krótkie zapytanie sprawdzające, czy klucz faktycznie działa.
     *
     * Limit tokenów jest z zapasem, bo modele „myślące” (Gemini 2.x) liczą
     * tokeny rozumowania do tego samego budżetu i przy ciasnym limicie
     * potrafią skończyć zanim napiszą choć słowo.
     */
    public function ping(string $provider): LlmResponse
    {
        return $this->generate(new LlmRequest(
            prompt: 'Odpowiedz dokładnie jednym słowem: OK',
            systemPrompt: 'Jesteś testem połączenia. Odpowiadaj maksymalnie krótko.',
            temperature: 0.0,
            maxTokens: 512,
        ), $provider);
    }
}
