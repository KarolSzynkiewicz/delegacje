<?php

namespace App\Services\Llm\Providers;

use App\Contracts\Llm\LlmProvider;

/**
 * Wspólna część adapterów opartych o HTTP: konfiguracja z config/llm.php.
 */
abstract class AbstractHttpProvider implements LlmProvider
{
    /**
     * @param  array<string, mixed>  $config  Wpis z config('llm.providers.*').
     */
    public function __construct(
        protected readonly string $key,
        protected readonly array $config,
    ) {}

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return (string) ($this->config['label'] ?? $this->key);
    }

    public function availableModels(): array
    {
        return (array) ($this->config['models'] ?? []);
    }

    public function defaultModel(): string
    {
        return (string) ($this->config['default_model'] ?? array_key_first($this->availableModels()) ?? '');
    }

    protected function baseUrl(): string
    {
        return rtrim((string) ($this->config['base_url'] ?? ''), '/');
    }

    protected function timeout(): int
    {
        return (int) config('llm.timeout', 60);
    }

    protected function maxTokens(?int $requested): int
    {
        return $requested ?? (int) config('llm.max_tokens', 2048);
    }
}
