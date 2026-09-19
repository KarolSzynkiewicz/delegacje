<?php

namespace App\Mcp\Concerns;

use App\Models\User;
use App\Services\UserMentionService;

trait ResolvesCalendarUser
{
    /**
     * Osoba, której kalendarz czytamy / zmieniamy.
     * Domyślnie konto MCP (OAuth albo MCP_ACTOR_USER_ID).
     *
     * @param  array<string, mixed>  $validated
     */
    protected function calendarUserFrom(array $validated, User $actor): User|string
    {
        if (! empty($validated['assigned_to'])) {
            $user = User::query()->find((int) $validated['assigned_to']);

            return $user ?? 'Nie znaleziono użytkownika o `assigned_to`='.$validated['assigned_to'].'.';
        }

        if ($validated['assigned_to_me'] ?? false) {
            return $actor;
        }

        if (! empty($validated['assignee_name'])) {
            $user = $this->resolveUserByName((string) $validated['assignee_name']);

            return $user ?? 'Nie znaleziono użytkownika „'.$validated['assignee_name'].'”. Sprawdź `list_users`.';
        }

        return $actor;
    }

    protected function resolveUserByName(string $name): ?User
    {
        return UserMentionService::resolveUserByMentionHandle($name)
            ?? User::query()->where('name', 'like', $name)->first();
    }
}
