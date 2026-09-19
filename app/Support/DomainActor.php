<?php

namespace App\Support;

use App\Models\User;

final class DomainActor
{
    public static function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
