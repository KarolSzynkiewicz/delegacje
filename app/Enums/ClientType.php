<?php

namespace App\Enums;

enum ClientType: string
{
    case Client = 'client';
    case ServiceProvider = 'service_provider';

    public function label(): string
    {
        return match ($this) {
            self::Client => 'Klient',
            self::ServiceProvider => 'Usługodawca / podwykonawca',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Client => 'Klient',
            self::ServiceProvider => 'Usługodawca',
        };
    }

    public function canOwnProjects(): bool
    {
        return $this === self::Client;
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
