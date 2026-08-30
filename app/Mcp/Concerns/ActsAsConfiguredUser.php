<?php

namespace App\Mcp\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

trait ActsAsConfiguredUser
{
    /**
     * Użytkownik, w imieniu którego działa narzędzie.
     *
     * HTTP MCP (OAuth / Passport) loguje rzeczywistego użytkownika.
     * Lokalny stdio nie ma sesji — wtedy fallback to MCP_ACTOR_USER_ID.
     */
    protected function actingUser(): User
    {
        $resolved = Auth::user() ?? Auth::guard('api')->user();

        if ($resolved instanceof User) {
            Auth::setUser($resolved);

            return $resolved;
        }

        $id = config('ai_tools.actor_user_id');

        if (blank($id)) {
            throw new RuntimeException(
                'Brak zalogowanego użytkownika MCP i MCP_ACTOR_USER_ID w .env.'
            );
        }

        $user = User::find($id);

        if (! $user) {
            throw new RuntimeException("Użytkownik o ID {$id} z MCP_ACTOR_USER_ID nie istnieje.");
        }

        Auth::setUser($user);

        return $user;
    }
}
