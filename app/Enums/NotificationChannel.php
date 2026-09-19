<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case Database = 'database';
    case Mail = 'mail';
    case Push = 'push';
    case WhatsApp = 'whatsapp';
    case Sms = 'sms';

    public function label(): string
    {
        return match ($this) {
            self::Database => 'Dzwonek w aplikacji',
            self::Mail => 'E-mail',
            self::Push => 'Push na telefon',
            self::WhatsApp => 'WhatsApp',
            self::Sms => 'SMS',
        };
    }

    /**
     * @return list<self>
     */
    public static function implemented(): array
    {
        return [self::Database];
    }

    public function laravelChannel(): string
    {
        return match ($this) {
            self::Database => 'database',
            self::Mail => 'mail',
            default => $this->value,
        };
    }
}
