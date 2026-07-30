<?php

namespace App\Enums;

enum RecruitmentConsentType: string
{
    case Rodo = 'rodo';
    case RecruitmentProcessing = 'recruitment_processing';
    case Marketing = 'marketing';

    public function label(): string
    {
        return match ($this) {
            self::Rodo => 'Zgoda RODO',
            self::RecruitmentProcessing => 'Zgoda na rekrutację (bieżącą i przyszłe)',
            self::Marketing => 'Zgoda marketingowa',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
