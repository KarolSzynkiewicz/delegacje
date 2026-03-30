<?php

namespace App\Enums;

enum ServiceActionType: string
{
    case REPAIR = 'repair';
    case SCHEDULED = 'scheduled';
    case USER_ERROR = 'user_error';
    case SERVICE_CHANGE = 'service_change';
    case INSPECTION = 'inspection';
    case TIRES = 'tires';
    case BODYWORK = 'bodywork';
    case DIAGNOSTIC = 'diagnostic';

    public function label(): string
    {
        return match($this) {
            self::REPAIR       => 'Naprawa awaryjna',
            self::SCHEDULED    => 'Planowany przegląd',
            self::USER_ERROR   => 'Usterka użytkownika',
            self::SERVICE_CHANGE => 'Wymiana serwisowa (olej, filtry)',
            self::INSPECTION   => 'Badanie techniczne',
            self::TIRES        => 'Wymiana opon',
            self::BODYWORK     => 'Blacharnia / lakiernia',
            self::DIAGNOSTIC   => 'Diagnostyka',
        };
    }

    public function badgeVariant(): string
    {
        return match($this) {
            self::REPAIR       => 'danger',
            self::SCHEDULED    => 'info',
            self::USER_ERROR   => 'warning',
            self::SERVICE_CHANGE => 'primary',
            self::INSPECTION   => 'success',
            self::TIRES        => 'secondary',
            self::BODYWORK     => 'accent',
            self::DIAGNOSTIC   => 'info',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
