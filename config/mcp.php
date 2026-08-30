<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Redirect Domains
    |--------------------------------------------------------------------------
    |
    | Domeny, z których klienci MCP (ChatGPT, Grok, …) mogą rejestrować
    | redirect URI przy dynamicznym OAuth. Gwiazdka zezwala na wszystkie.
    | Na produkcji warto zawęzić, np. https://chatgpt.com,https://grok.com
    |
    */

    'redirect_domains' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('MCP_OAUTH_REDIRECT_DOMAINS', '*'))
    ))),

];
