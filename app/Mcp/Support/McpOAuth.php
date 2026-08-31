<?php

namespace App\Mcp\Support;

use App\Mcp\Http\RegisterOAuthClientController;
use App\Mcp\OAuth\McpRefreshTokenGrant;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\AuthorizationServer;

class McpOAuth
{
    /**
     * Discovery OAuth 2.1 + dynamiczna rejestracja klienta.
     *
     * Nie używamy Mcp::oauthRoutes() z laravel/mcp — tamten rejestrator
     * wymaga Passport 13 (Laravel 11+).
     */
    public static function routes(): void
    {
        Route::get('/.well-known/oauth-protected-resource/{path?}', function (?string $path = '') {
            $resource = $path !== '' && $path !== null
                ? url('/'.$path)
                : url('/mcp/tasks');

            return response()->json([
                'resource' => $resource,
                'authorization_servers' => [url('/')],
                'scopes_supported' => ['mcp:use'],
            ]);
        })->where('path', '.*')->name('mcp.oauth.protected-resource');

        Route::get('/.well-known/oauth-authorization-server/{path?}', function (?string $path = '') {
            return response()->json([
                'issuer' => url('/'),
                'authorization_endpoint' => route('passport.authorizations.authorize'),
                'token_endpoint' => route('passport.token'),
                'registration_endpoint' => url('/oauth/register'),
                'response_types_supported' => ['code'],
                'code_challenge_methods_supported' => ['S256'],
                'scopes_supported' => ['mcp:use'],
                'grant_types_supported' => ['authorization_code', 'refresh_token'],
                'token_endpoint_auth_methods_supported' => ['none'],
            ]);
        })->where('path', '.*')->name('mcp.oauth.authorization-server');

        Route::post('/oauth/register', RegisterOAuthClientController::class)
            ->middleware('throttle:10,1');
    }

    /**
     * Podmień grant refresh_token na wariant przyjazny klientom MCP.
     */
    public static function configureAuthorizationServer(AuthorizationServer $server): void
    {
        $grant = new McpRefreshTokenGrant(app(RefreshTokenRepository::class));
        $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());
        $server->enableGrantType($grant, Passport::tokensExpireIn());
    }
}
