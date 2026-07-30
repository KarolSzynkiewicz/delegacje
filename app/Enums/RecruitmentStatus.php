<?php

namespace App\Enums;

enum RecruitmentStatus: string
{
    case Nowy = 'nowy';
    case WTrakcieKontaktu = 'w_trakcie_kontaktu';
    case Zaakceptowany = 'zaakceptowany';
    case Odrzucony = 'odrzucony';
    case Onboarding = 'onboarding';
    case Zatrudniony = 'zatrudniony';
    case BylyPracownik = 'byly_pracownik';

    public function label(): string
    {
        return match ($this) {
            self::Nowy => 'Nowy',
            self::WTrakcieKontaktu => 'W trakcie kontaktu',
            self::Zaakceptowany => 'Zaakceptowany',
            self::Odrzucony => 'Odrzucony',
            self::Onboarding => 'Onboarding',
            self::Zatrudniony => 'Zatrudniony',
            self::BylyPracownik => 'Były pracownik',
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::Nowy => 'info',
            self::WTrakcieKontaktu => 'primary',
            self::Zaakceptowany => 'success',
            self::Odrzucony => 'danger',
            self::Onboarding => 'warning',
            self::Zatrudniony => 'success',
            self::BylyPracownik => 'secondary',
        };
    }

    /**
     * Order in which the status tabs are displayed in the pipeline view.
     *
     * @return array<int, self>
     */
    public static function tabOrder(): array
    {
        return [
            self::Nowy,
            self::WTrakcieKontaktu,
            self::Zaakceptowany,
            self::Odrzucony,
            self::Onboarding,
            self::Zatrudniony,
            self::BylyPracownik,
        ];
    }

    /**
     * Main pipeline flow for the visual pipeline component.
     * Returns the linear steps; Odrzucony and BylyPracownik are side-exits.
     *
     * @return array<int, self>
     */
    public static function pipelineSteps(): array
    {
        return [
            self::Nowy,
            self::WTrakcieKontaktu,
            self::Zaakceptowany,
            self::Onboarding,
            self::Zatrudniony,
        ];
    }

    /** Returns the 0-based index of this status in the pipeline flow, or null if it's a side-exit. */
    public function pipelineIndex(): ?int
    {
        $idx = array_search($this, self::pipelineSteps(), true);
        return $idx === false ? null : $idx;
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
