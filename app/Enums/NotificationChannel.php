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

    public function columnLabel(): string
    {
        return match ($this) {
            self::Database => 'W aplikacji',
            self::Push => 'Push',
            default => $this->label(),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Database => 'bi-bell',
            self::Mail => 'bi-envelope',
            self::Push => 'bi-phone',
            self::WhatsApp => 'bi-whatsapp',
            self::Sms => 'bi-chat-dots',
        };
    }

    /**
     * Kanały, które router naprawdę wysyła.
     *
     * @return list<self>
     */
    public static function implemented(): array
    {
        return [self::Database, self::Push];
    }

    /**
     * Kolumny macierzy w profilu.
     *
     * @return list<self>
     */
    public static function preferenceColumns(): array
    {
        return [self::Database, self::Push];
    }

    public function laravelChannel(): string
    {
        return match ($this) {
            self::Database => 'database',
            self::Mail => 'mail',
            self::Push => \NotificationChannels\WebPush\WebPushChannel::class,
            default => $this->value,
        };
    }
}
