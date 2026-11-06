<?php

namespace App\Enums;

enum ClientContactPurpose: string
{
    case HoursSettlement = 'hours_settlement';
    case General = 'general';
    case Contract = 'contract';
    case FollowUp = 'follow_up';
    case Complaint = 'complaint';

    public function label(): string
    {
        return match ($this) {
            self::HoursSettlement => 'Ustalenie godzin',
            self::General => 'Kontakt ogólny',
            self::Contract => 'Umowa / warunki',
            // Legacy — nie wybieralne; follow-up = data „Oddzwoń” na wpisie.
            self::FollowUp => 'Kontakt ogólny',
            self::Complaint => 'Reklamacja',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->reject(fn (self $case) => $case === self::FollowUp)
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
