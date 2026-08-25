<?php

namespace App\Mcp\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

trait ActsAsConfiguredUser
{
    /**
     * Zaloguj użytkownika skonfigurowanego w MCP_ACTOR_USER_ID.
     *
     * Bez tego auth()->id() jest puste, a część modeli i polityk (np.
     * ProjectTask::callbackStory) zakłada zalogowanego użytkownika.
     */
    protected function actingUser(): User
    {
        if ($user = Auth::user()) {
            return $user;
        }

        $id = config('ai_tools.actor_user_id');

        if (blank($id)) {
            throw new RuntimeException(
                'Brak MCP_ACTOR_USER_ID w .env – ustaw ID użytkownika, w imieniu którego działa serwer MCP.'
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
