<?php

namespace App\Enums;

enum RoleSeniority: int
{
    case Trainee = 1;
    case Basic = 2;
    case Independent = 3;
    case Expert = 4;

    public function label(): string
    {
        return match ($this) {
            self::Trainee => 'Przyuczenie',
            self::Basic => 'Podstawowa samodzielność',
            self::Independent => 'Samodzielny fachowiec',
            self::Expert => 'Ekspert',
        };
    }

    public function shortLabel(): string
    {
        return (string) $this->value;
    }

    public function hint(): string
    {
        return match ($this) {
            self::Trainee => 'Wymaga stałego wsparcia i nadzoru.',
            self::Basic => 'Wykonuje standardową pracę, przy trudniejszych zadaniach potrzebuje wsparcia.',
            self::Independent => 'Samodzielnie realizuje pełny standardowy zakres pracy.',
            self::Expert => 'Może prowadzić innych i być punktem odniesienia dla zespołu.',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Trainee => 'warning',
            self::Basic => 'info',
            self::Independent => 'success',
            self::Expert => 'accent',
        };
    }

    public static function fromPivot(mixed $value): ?self
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::tryFrom((int) $value);
    }

    /**
     * @return array<int, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->value.' — '.$case->label()])
            ->all();
    }
}
