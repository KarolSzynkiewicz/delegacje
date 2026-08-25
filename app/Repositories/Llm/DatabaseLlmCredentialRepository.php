<?php

namespace App\Repositories\Llm;

use App\Contracts\Llm\LlmCredentialRepository;
use App\Models\LlmCredential;
use App\Support\Llm\ProviderCredentials;
use Illuminate\Support\Facades\DB;

/**
 * Klucze trzymane w bazie (szyfrowane), z fallbackiem na .env.
 *
 * Dzięki fallbackowi ten sam kod działa na Railway, gdzie klucz może przyjść
 * ze zmiennej środowiskowej zamiast z formularza w UI.
 */
class DatabaseLlmCredentialRepository implements LlmCredentialRepository
{
    public function find(string $provider): ?ProviderCredentials
    {
        $record = LlmCredential::query()->where('provider', $provider)->first();

        if ($record && filled($record->api_key)) {
            return new ProviderCredentials(
                provider: $provider,
                apiKey: (string) $record->api_key,
                model: $record->model,
                source: 'database',
            );
        }

        $envKey = config("llm.providers.{$provider}.api_key");

        if (filled($envKey)) {
            return new ProviderCredentials(
                provider: $provider,
                apiKey: (string) $envKey,
                model: config("llm.providers.{$provider}.default_model"),
                source: 'env',
            );
        }

        return null;
    }

    public function store(string $provider, string $apiKey, ?string $model = null, ?int $userId = null): void
    {
        LlmCredential::query()->updateOrCreate(
            ['provider' => $provider],
            [
                'api_key' => $apiKey,
                'model' => $model,
                'created_by' => $userId,
            ],
        );
    }

    public function updateModel(string $provider, ?string $model): void
    {
        LlmCredential::query()
            ->where('provider', $provider)
            ->update(['model' => $model]);
    }

    public function forget(string $provider): void
    {
        LlmCredential::query()->where('provider', $provider)->delete();
    }

    public function activate(string $provider): void
    {
        DB::transaction(function () use ($provider) {
            LlmCredential::query()->where('provider', '!=', $provider)->update(['is_active' => false]);
            LlmCredential::query()->where('provider', $provider)->update(['is_active' => true]);
        });
    }

    public function activeProvider(): ?string
    {
        $active = LlmCredential::query()->where('is_active', true)->value('provider');

        if ($active) {
            return $active;
        }

        // Brak jawnego wyboru: bierzemy pierwszego, który ma klucz, zaczynając od domyślnego z konfiguracji.
        $configured = $this->configuredProviders();

        if ($configured === []) {
            return null;
        }

        $default = (string) config('llm.default');

        return in_array($default, $configured, true) ? $default : $configured[0];
    }

    public function configuredProviders(): array
    {
        $fromDatabase = LlmCredential::query()->pluck('provider')->all();

        $fromEnv = collect(config('llm.providers', []))
            ->filter(fn (array $config) => filled($config['api_key'] ?? null))
            ->keys()
            ->all();

        return collect($fromDatabase)
            ->merge($fromEnv)
            ->unique()
            ->values()
            ->all();
    }

    public function touchLastUsed(string $provider): void
    {
        LlmCredential::query()
            ->where('provider', $provider)
            ->update(['last_used_at' => now()]);
    }
}
