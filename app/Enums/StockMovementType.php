<?php

namespace App\Enums;

enum StockMovementType: string
{
    case CONSUMPTION = 'consumption';
    case RECEIPT = 'receipt';
    case ADJUSTMENT = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::CONSUMPTION => 'Rozchód',
            self::RECEIPT => 'Przyjęcie',
            self::ADJUSTMENT => 'Korekta',
        };
    }
}
