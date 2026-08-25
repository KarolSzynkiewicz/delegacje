<?php

use App\Services\Llm\Providers\GeminiProvider;
use App\Services\Llm\Providers\OpenAiCompatibleProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Dostawca domyślny
    |--------------------------------------------------------------------------
    |
    | Używany, dopóki nikt nie wskaże aktywnego dostawcy w Akcjach systemowych.
    |
    */

    'default' => env('LLM_PROVIDER', 'gemini'),

    /*
    |--------------------------------------------------------------------------
    | Rejestr dostawców
    |--------------------------------------------------------------------------
    |
    | Dodanie kolejnego dostawcy to nowa klasa implementująca LlmProvider
    | plus wpis poniżej — reszta aplikacji zna wyłącznie interfejs LlmClient.
    | Klucze `api_key` są tylko fallbackiem dla .env; normalnie klucz trafia
    | zaszyfrowany do tabeli `llm_credentials` przez formularz w UI.
    |
    */

    'providers' => [

        'gemini' => [
            'driver' => GeminiProvider::class,
            'label' => 'Google AI Studio (Gemini)',
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
            'api_key' => env('GEMINI_API_KEY'),
            'default_model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
            // null = model sam decyduje ile „myśli”; 0 = bez myślenia (taniej i szybciej).
            'thinking_budget' => env('GEMINI_THINKING_BUDGET'),
            'models' => [
                'gemini-2.5-flash' => 'Gemini 2.5 Flash — szybki, tani',
                'gemini-2.5-pro' => 'Gemini 2.5 Pro — najmocniejszy',
                'gemini-2.0-flash' => 'Gemini 2.0 Flash',
            ],
            'key_url' => 'https://aistudio.google.com/app/apikey',
        ],

        'openai' => [
            'driver' => OpenAiCompatibleProvider::class,
            'label' => 'OpenAI (i API zgodne z OpenAI)',
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'api_key' => env('OPENAI_API_KEY'),
            'default_model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'models' => [
                'gpt-4o-mini' => 'GPT-4o mini — szybki, tani',
                'gpt-4o' => 'GPT-4o',
            ],
            'key_url' => 'https://platform.openai.com/api-keys',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Limity żądań
    |--------------------------------------------------------------------------
    */

    'timeout' => (int) env('LLM_TIMEOUT', 60),

    'max_tokens' => (int) env('LLM_MAX_TOKENS', 2048),

];
