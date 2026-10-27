<?php

namespace App\Enums;

enum OfficeWorkLocation: string
{
    case Office = 'office';
    case Remote = 'remote';
    case Leave = 'leave';

    public function label(): string
    {
        return match ($this) {
            self::Office => 'Biuro',
            self::Remote => 'Zdalnie',
            self::Leave => 'Urlop',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Office => 'B',
            self::Remote => 'Z',
            self::Leave => 'U',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Office => 'success',
            self::Remote => 'info',
            self::Leave => 'accent',
        };
    }

    public function next(): self
    {
        return match ($this) {
            self::Office => self::Remote,
            self::Remote => self::Leave,
            self::Leave => self::Office,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
