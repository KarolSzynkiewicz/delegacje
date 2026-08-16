<?php

namespace App\Enums;

enum StockMovementReason: string
{
    case Purchase = 'purchase';
    case ReturnToStock = 'return_to_stock';
    case Recovery = 'recovery';
    case InventorySurplus = 'inventory_surplus';

    case InventoryShortage = 'inventory_shortage';
    case Destruction = 'destruction';
    case Loss = 'loss';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Zakup',
            self::ReturnToStock => 'Zwrot na magazyn',
            self::Recovery => 'Odzyskanie',
            self::InventorySurplus => 'Nadwyżka inwentaryzacyjna',
            self::InventoryShortage => 'Brak w inwentarzu',
            self::Destruction => 'Zniszczenie',
            self::Loss => 'Utrata',
            self::Expired => 'Przeterminowanie',
        };
    }

    public function movementType(): StockMovementType
    {
        return $this->isInbound()
            ? StockMovementType::RECEIPT
            : StockMovementType::ADJUSTMENT;
    }

    public function isInbound(): bool
    {
        return in_array($this, [
            self::Purchase,
            self::ReturnToStock,
            self::Recovery,
            self::InventorySurplus,
        ], true);
    }

    public function appliesTo(StockMovementType $type): bool
    {
        return $this->movementType() === $type;
    }

    /**
     * @return list<self>
     */
    public static function forType(StockMovementType $type): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $reason) => $reason->appliesTo($type)
        ));
    }
}
