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
            self::Zaakceptowany => 'Weryfikacja',
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

    /** Side-exits (Odrzucony, Były pracownik) sit next to the flow, not on it. */
    public function isPipelineExit(): bool
    {
        return $this->pipelineIndex() === null;
    }

    /**
     * Stage the process moves to when it progresses. Side-exits re-enter the
     * funnel at the contact stage instead of jumping to the end.
     */
    public function nextPipelineStatus(): ?self
    {
        $idx = $this->pipelineIndex();

        if ($idx === null) {
            return self::WTrakcieKontaktu;
        }

        return self::pipelineSteps()[$idx + 1] ?? null;
    }

    public function previousPipelineStatus(): ?self
    {
        $idx = $this->pipelineIndex();

        return $idx === null || $idx === 0 ? null : self::pipelineSteps()[$idx - 1];
    }

    public function procedureSlotKey(): ?string
    {
        return match ($this) {
            self::Zaakceptowany => 'recruitment_process.zaakceptowany',
            self::Onboarding => 'recruitment_process.onboarding',
            self::Zatrudniony => 'recruitment_process.zatrudniony',
            default => null,
        };
    }

    public function procedureSlotLabel(): ?string
    {
        return match ($this) {
            self::Zaakceptowany => 'Procedura: Weryfikacja',
            self::Onboarding => 'Procedura: Onboarding',
            self::Zatrudniony => 'Procedura: Zatrudniony',
            default => null,
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
