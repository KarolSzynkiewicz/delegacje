<?php

namespace App\Enums;

enum NotificationEventGroup: string
{
    case Work = 'work';
    case Social = 'social';
    case Procedure = 'procedure';

    public function label(): string
    {
        return match ($this) {
            self::Work => 'Praca',
            self::Social => 'Komentarze',
            self::Procedure => 'Procedury',
        };
    }
}
