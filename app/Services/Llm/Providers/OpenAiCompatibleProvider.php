<?php

namespace App\Services\Llm\Providers;

use App\Exceptions\LlmException;
use App\Support\Llm\LlmRequest;
use App\Support\Llm\LlmResponse;
use App\Support\Llm\ProviderCredentials;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Adapter dla `POST /chat/completions` — OpenAI, ale też OpenRouter, Groq
 * czy lokalna Ollama, wystarczy zmienić `base_url` w config/llm.php.
 *
 * Istnieje po to, żeby podmiana dostawcy była faktycznie jedną zmianą
 * w ustawieniach, a nie refaktorem kodu domenowego.
 */
class OpenAiCompatibleProvider extends AbstractHttpProvider
{
    public function generate(LlmRequest $request, ProviderCredentials $credentials): LlmResponse
    {
        $model = $request->model ?: ($credentials->model ?: $this->defaultModel());

        $messages = [];

        if (filled($request->systemPrompt)) {
            $messages[] = ['role' => 'system', 'content' => $request->systemPrompt];
        }

        $messages[] = ['role' => 'user', 'content' => $request->prompt];

        try {
            $body = [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $request->temperature,
                'max_tokens' => $this->maxTokens($request->maxTokens),
            ];

            if ($request->jsonMode) {
                $body['response_format'] = ['type' => 'json_object'];
            }

            $response = Http::withToken($credentials->apiKey)
                ->timeout($this->timeout())
                ->post($this->baseUrl().'/chat/completions', $body);
        } catch (ConnectionException $e) {
            throw LlmException::transportError($this->key(), $e->getMessage());
        }

        if ($response->failed()) {
            throw LlmException::requestFailed($this->key(), $response->status(), $response->body());
        }

        $data = $response->json() ?? [];
        $text = $data['choices'][0]['message']['content'] ?? '';

        if (blank($text)) {
            throw LlmException::emptyResponse($this->key());
        }

        return new LlmResponse(
            text: $text,
            provider: $this->key(),
            model: $data['model'] ?? $model,
            promptTokens: $data['usage']['prompt_tokens'] ?? null,
            completionTokens: $data['usage']['completion_tokens'] ?? null,
            raw: $data,
        );
    }
}
