<?php

namespace App\Enums;

enum WorkItemTimeBlockKind: string
{
    case Item = 'item';
    case Session = 'session';

    public function label(): string
    {
        return match ($this) {
            self::Item => 'Blok',
            self::Session => 'Sesja',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Item => 'bi-clock',
            self::Session => 'bi-bag',
        };
    }
}
