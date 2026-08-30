<?php

namespace App\Mcp\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;

class RegisterOAuthClientController
{
    /**
     * Dynamic Client Registration (RFC 7591) dla klientów MCP.
     *
     * Laravel MCP woła Passport 13 (`createAuthorizationCodeGrantClient`).
     * Na Laravel 10 / Passport 11 mapujemy to na publicznego klienta PKCE.
     */
    public function __invoke(Request $request, ClientRepository $clients): JsonResponse
    {
        $validated = $request->validate([
            'redirect_uris' => ['required', 'array', 'min:1'],
            'redirect_uris.*' => ['required', 'url', function (string $attribute, mixed $value, \Closure $fail): void {
                if (in_array('*', config('mcp.redirect_domains', []), true)) {
                    return;
                }

                if (! Str::startsWith((string) $value, $this->allowedDomains())) {
                    $fail($attribute.' is not a permitted redirect domain.');
                }
            }],
        ]);

        $name = $request->input('client_name')
            ?: $request->input('name')
            ?: 'MCP Client';

        $client = $clients->create(
            null,
            $name,
            implode(',', $validated['redirect_uris']),
            null,
            false,
            false,
            false
        );

        return response()->json([
            'client_id' => (string) $client->getKey(),
            'client_name' => $client->name,
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'redirect_uris' => explode(',', (string) $client->redirect),
            'scope' => 'mcp:use',
            'token_endpoint_auth_method' => 'none',
        ], 201);
    }

    /**
     * @return array<int, string>
     */
    protected function allowedDomains(): array
    {
        /** @var array<int, string> $allowedDomains */
        $allowedDomains = config('mcp.redirect_domains', []);

        return collect($allowedDomains)
            ->map(fn (string $domain): string => Str::endsWith($domain, '/')
                ? $domain
                : "{$domain}/"
            )
            ->all();
    }
}
