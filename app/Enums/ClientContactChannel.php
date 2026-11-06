<?php

namespace App\Enums;

enum ClientContactChannel: string
{
    case Phone = 'phone';
    case Email = 'email';
    case Meeting = 'meeting';
    case Chat = 'chat';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Phone => 'Telefon',
            self::Email => 'E-mail',
            self::Meeting => 'Spotkanie',
            self::Chat => 'Chat / Teams',
            self::Other => 'Inne',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Phone => 'bi-telephone',
            self::Email => 'bi-envelope',
            self::Meeting => 'bi-people',
            self::Chat => 'bi-chat-dots',
            self::Other => 'bi-three-dots',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
