<?php

namespace App\Enums;

enum ProcedureRunStatus: string
{
    case IN_PROGRESS = 'in_progress';
    case FINISHED    = 'finished';
    case ABANDONED   = 'abandoned';

    public function label(): string
    {
        return match($this) {
            self::IN_PROGRESS => 'W trakcie',
            self::FINISHED    => 'Zakończona',
            self::ABANDONED   => 'Porzucona',
        };
    }

    public function badgeVariant(): string
    {
        return match($this) {
            self::IN_PROGRESS => 'info',
            self::FINISHED    => 'success',
            self::ABANDONED   => 'secondary',
        };
    }
}
