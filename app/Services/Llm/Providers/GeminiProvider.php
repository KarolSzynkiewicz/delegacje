<?php

namespace App\Services\Llm\Providers;

use App\Exceptions\LlmException;
use App\Support\Llm\LlmRequest;
use App\Support\Llm\LlmResponse;
use App\Support\Llm\ProviderCredentials;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Google AI Studio / Gemini — endpoint `generateContent`.
 *
 * Klucz idzie nagłówkiem `x-goog-api-key`, nie w query stringu, żeby nie
 * lądował w logach proxy ani w komunikatach o błędach HTTP.
 */
class GeminiProvider extends AbstractHttpProvider
{
    public function generate(LlmRequest $request, ProviderCredentials $credentials): LlmResponse
    {
        $model = $request->model ?: ($credentials->model ?: $this->defaultModel());

        $payload = [
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => $request->prompt]],
            ]],
            'generationConfig' => [
                'temperature' => $request->temperature,
                'maxOutputTokens' => $this->maxTokens($request->maxTokens),
            ],
        ];

        if (filled($request->systemPrompt)) {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $request->systemPrompt]],
            ];
        }

        // Modele 2.x „myślą” przed odpowiedzią, a tokeny rozumowania liczą się do
        // maxOutputTokens. Budżet 0 wyłącza myślenie tam, gdzie liczy się koszt.
        $thinkingBudget = $this->config['thinking_budget'] ?? null;

        if ($request->jsonMode) {
            $payload['generationConfig']['responseMimeType'] = 'application/json';
            // Modele 2.x „myślą” w ramach maxOutputTokens — przy JSON potrafią
            // zużyć cały budżet zanim napiszą choć otwierający nawias.
            $payload['generationConfig']['thinkingConfig'] = ['thinkingBudget' => 0];
        } elseif ($thinkingBudget !== null) {
            $payload['generationConfig']['thinkingConfig'] = ['thinkingBudget' => (int) $thinkingBudget];
        }

        try {
            $response = Http::withHeaders([
                'x-goog-api-key' => $credentials->apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout($this->timeout())
                ->post($this->baseUrl()."/models/{$model}:generateContent", $payload);
        } catch (ConnectionException $e) {
            throw LlmException::transportError($this->key(), $e->getMessage());
        }

        if ($response->failed()) {
            throw LlmException::requestFailed($this->key(), $response->status(), $response->body());
        }

        $data = $response->json() ?? [];

        $text = collect($data['candidates'][0]['content']['parts'] ?? [])
            ->pluck('text')
            ->filter()
            ->implode('');

        if (blank($text)) {
            throw LlmException::emptyResponse($this->key(), $this->explainMissingText($data));
        }

        return new LlmResponse(
            text: $text,
            provider: $this->key(),
            model: $data['modelVersion'] ?? $model,
            promptTokens: $data['usageMetadata']['promptTokenCount'] ?? null,
            completionTokens: $data['usageMetadata']['candidatesTokenCount'] ?? null,
            raw: $data,
        );
    }

    /**
     * Pusta treść przy statusie 200 to zwykle limit tokenów albo filtr treści.
     * Bez tego wyjaśnienia błąd w UI jest nie do zdiagnozowania.
     *
     * @param  array<string, mixed>  $data
     */
    private function explainMissingText(array $data): ?string
    {
        if ($blockReason = $data['promptFeedback']['blockReason'] ?? null) {
            return "Zapytanie zostało zablokowane przez filtry ({$blockReason}).";
        }

        $finishReason = $data['candidates'][0]['finishReason'] ?? null;

        if ($finishReason === 'MAX_TOKENS') {
            $thoughts = $data['usageMetadata']['thoughtsTokenCount'] ?? null;

            return 'Limit tokenów wyczerpał się zanim model zaczął pisać odpowiedź'
                .($thoughts ? " (na samo „myślenie” poszło {$thoughts} tokenów)" : '')
                .'. Zwiększ LLM_MAX_TOKENS albo ustaw GEMINI_THINKING_BUDGET=0.';
        }

        if ($finishReason !== null && $finishReason !== 'STOP') {
            return "Model przerwał generowanie: {$finishReason}.";
        }

        return null;
    }
}
