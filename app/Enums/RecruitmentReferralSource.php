<?php

namespace App\Enums;

enum RecruitmentReferralSource: string
{
    case SocialMedia = 'social_media';
    case EmployeeReferral = 'employee_referral';
    case Recommendation = 'recommendation';
    case JobPortal = 'job_portal';

    public function label(): string
    {
        return match ($this) {
            self::SocialMedia => 'Reklama na social mediach',
            self::EmployeeReferral => 'Polecenie przez pracownika',
            self::Recommendation => 'Rekomendacje',
            self::JobPortal => 'Portal z ogłoszeniami o pracę',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
