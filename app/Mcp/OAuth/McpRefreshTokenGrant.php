<?php

namespace App\Mcp\OAuth;

use DateInterval;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\RequestAccessTokenEvent;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\RequestRefreshTokenEvent;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Refresh token dla klientów MCP (Grok, ChatGPT).
 *
 * Standardowa rotacja unieważnia stary refresh przy każdym odświeżeniu.
 * Klienci MCP często nie zapisują nowego refresh_token albo odświeżają
 * równolegle — wtedy connector „umiera” do ponownego OAuth.
 *
 * Tu: nowy access + nowy refresh, ale stary refresh zostaje ważny.
 */
class McpRefreshTokenGrant extends RefreshTokenGrant
{
    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTTL
    ) {
        $client = $this->validateClient($request);
        $oldRefreshToken = $this->validateOldRefreshToken($request, $client->getIdentifier());
        $scopes = $this->validateScopes(
            $this->getRequestParameter(
                'scope',
                $request,
                implode(self::SCOPE_DELIMITER_STRING, $oldRefreshToken['scopes'])
            )
        );

        foreach ($scopes as $scope) {
            if (in_array($scope->getIdentifier(), $oldRefreshToken['scopes'], true) === false) {
                throw OAuthServerException::invalidScope($scope->getIdentifier());
            }
        }

        $this->accessTokenRepository->revokeAccessToken($oldRefreshToken['access_token_id']);

        $accessToken = $this->issueAccessToken($accessTokenTTL, $client, $oldRefreshToken['user_id'], $scopes);
        $this->getEmitter()->emit(new RequestAccessTokenEvent(RequestEvent::ACCESS_TOKEN_ISSUED, $request, $accessToken));
        $responseType->setAccessToken($accessToken);

        $refreshToken = $this->issueRefreshToken($accessToken);

        if ($refreshToken !== null) {
            $this->getEmitter()->emit(new RequestRefreshTokenEvent(RequestEvent::REFRESH_TOKEN_ISSUED, $request, $refreshToken));
            $responseType->setRefreshToken($refreshToken);
        }

        return $responseType;
    }
}
