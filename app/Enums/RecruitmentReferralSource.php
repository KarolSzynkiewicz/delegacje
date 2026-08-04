<?php

namespace App\Enums;

enum RecruitmentReferralSource: string
{
    // --- channels available in the public application form ---
    case MetaBusinessSuite = 'meta_business_suite';
    case OLX = 'olx';
    case Trojmiasto = 'trojmiasto';
    case PracujPl = 'pracuj_pl';
    case LinkedIn = 'linkedin';
    case JobPortalOther = 'job_portal_other';    // follow-up: which portal?
    case EmployeeReferral = 'employee_referral';   // follow-up: which employee?

    // --- internal only (added by recruiter, not shown in public form) ---
    case Messenger = 'messenger';
    case ContactCenter = 'contact_center';
    case SystemBackfill = 'system_backfill';
    case EmployeeLifecycle = 'employee_lifecycle';
    case HistoricalImport = 'historical_import';

    public function label(): string
    {
        return match ($this) {
            self::MetaBusinessSuite => 'Meta Business Suite (FB/IG)',
            self::OLX => 'OLX',
            self::Trojmiasto => 'Trojmiasto.pl',
            self::PracujPl => 'Pracuj.pl',
            self::LinkedIn => 'LinkedIn',
            self::JobPortalOther => 'Portal z ogłoszeniem o pracę (inny)',
            self::EmployeeReferral => 'Polecenie przez pracownika',
            self::Messenger => 'Messenger / wiadomość bezpośrednia',
            self::ContactCenter => 'Contact center',
            self::SystemBackfill => 'Backfill systemowy (pracownik → kandydat)',
            self::EmployeeLifecycle => 'Cykl życia pracownika',
            self::HistoricalImport => 'Import historyczny (baza kandydatów)',
        };
    }

    /** Whether this source should appear in the public candidate form. */
    public function isPublicForm(): bool
    {
        return match ($this) {
            self::Messenger, self::ContactCenter, self::SystemBackfill, self::EmployeeLifecycle, self::HistoricalImport => false,
            default => true,
        };
    }

    /** Whether this source requires a free-text detail field. */
    public function requiresDetail(): bool
    {
        return match ($this) {
            self::JobPortalOther, self::EmployeeReferral => true,
            default => false,
        };
    }

    public function detailPlaceholder(): string
    {
        return match ($this) {
            self::JobPortalOther => 'Nazwa portalu…',
            self::EmployeeReferral => 'Imię i nazwisko pracownika…',
            default => '',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }

    public static function publicFormOptions(): array
    {
        return collect(self::cases())
            ->filter(fn (self $case) => $case->isPublicForm())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
