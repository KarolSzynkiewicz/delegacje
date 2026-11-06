<?php

namespace App\Enums;

enum ClientContactOutcome: string
{
    case Reached = 'reached';
    case NoAnswer = 'no_answer';
    case Callback = 'callback';
    case Agreed = 'agreed';
    case Postponed = 'postponed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Reached => 'Nawiązano kontakt',
            self::NoAnswer => 'Brak odpowiedzi',
            self::Callback => 'Proszą o oddzwonienie',
            self::Agreed => 'Uzgodniono',
            self::Postponed => 'Odłożono',
            self::Rejected => 'Odrzucono',
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::Reached => 'info',
            self::NoAnswer => 'danger',
            self::Callback => 'warning',
            self::Agreed => 'success',
            self::Postponed => 'accent',
            self::Rejected => 'danger',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
