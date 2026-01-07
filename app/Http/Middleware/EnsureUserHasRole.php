<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        // Admin zawsze ma dostęp
        if ($user && $user->isAdmin()) {
            return $next($request);
        }
        
        // Sprawdź czy użytkownik ma jakąkolwiek rolę
        if ($user && $user->roles()->count() === 0) {
            // Użytkownik bez roli - przekieruj do widoku z komunikatem
            // Pozwól na dostęp do: no-role, profile, logout
            if (!$request->routeIs('no-role') && !$request->routeIs('profile.*') && !$request->routeIs('logout')) {
                return redirect()->route('no-role');
            }
        }
        
        return $next($request);
    }
}
