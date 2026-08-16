<?php

namespace App\Enums;

enum StockMovementType: string
{
    case CONSUMPTION = 'consumption';
    case RECEIPT = 'receipt';
    case ADJUSTMENT = 'adjustment';
    case TRANSFER_OUT = 'transfer_out';
    case TRANSFER_IN = 'transfer_in';

    public function label(): string
    {
        return match ($this) {
            self::CONSUMPTION => 'Rozchód',
            self::RECEIPT => 'Przyjęcie towaru',
            self::ADJUSTMENT => 'Korekta inwentaryzacyjna',
            self::TRANSFER_OUT => 'Przemieszczenie (wyjście)',
            self::TRANSFER_IN => 'Przemieszczenie (wejście)',
        };
    }

    public function increasesStock(): bool
    {
        return in_array($this, [self::RECEIPT, self::TRANSFER_IN], true);
    }

    public function isTransfer(): bool
    {
        return in_array($this, [self::TRANSFER_OUT, self::TRANSFER_IN], true);
    }

    public function dotColor(): string
    {
        return match ($this) {
            self::RECEIPT => '#14b8a6',
            self::TRANSFER_OUT, self::TRANSFER_IN => '#38bdf8',
            self::CONSUMPTION, self::ADJUSTMENT => '#f43f5e',
        };
    }
}
