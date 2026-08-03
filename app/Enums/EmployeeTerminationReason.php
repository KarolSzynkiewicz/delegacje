<?php

namespace App\Enums;

enum EmployeeTerminationReason: string
{
    case Disciplinary = 'disciplinary';
    case ContractExpired = 'contract_expired';
    case Resignation = 'resignation';
    case MutualAgreement = 'mutual_agreement';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Disciplinary => 'Zwolnienie dyscyplinarne',
            self::ContractExpired => 'Koniec umowy',
            self::Resignation => 'Rezygnacja pracownika',
            self::MutualAgreement => 'Porozumienie stron',
            self::Other => 'Inny powód',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
