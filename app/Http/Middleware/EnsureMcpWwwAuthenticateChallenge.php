<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMcpWwwAuthenticateChallenge
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->is('mcp/*') || $response->getStatusCode() !== 401) {
            return $response;
        }

        $metadata = route('mcp.oauth.protected-resource', ['path' => $request->path()]);

        $response->headers->set(
            'WWW-Authenticate',
            'Bearer realm="mcp", error="invalid_token", resource_metadata="'.$metadata.'"'
        );

        return $response;
    }
}
