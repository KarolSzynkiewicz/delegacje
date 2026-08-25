<?php

namespace App\Contracts\Llm;

use App\Support\Llm\ProviderCredentials;

/**
 * Źródło kluczy API — dziś baza danych, jutro równie dobrze Vault czy .env.
 */
interface LlmCredentialRepository
{
    public function find(string $provider): ?ProviderCredentials;

    /**
     * Zapisz albo nadpisz klucz dostawcy.
     */
    public function store(string $provider, string $apiKey, ?string $model = null, ?int $userId = null): void;

    /**
     * Zmień model bez ruszania klucza.
     */
    public function updateModel(string $provider, ?string $model): void;

    public function forget(string $provider): void;

    /**
     * Ustaw dostawcę używanego domyślnie przez LlmClient.
     */
    public function activate(string $provider): void;

    public function activeProvider(): ?string;

    /**
     * Odnotuj udane użycie klucza — do diagnozy „czy ten dostawca w ogóle działa”.
     */
    public function touchLastUsed(string $provider): void;

    /**
     * Dostawcy, którzy mają zapisany klucz.
     *
     * @return array<int, string>
     */
    public function configuredProviders(): array;
}
