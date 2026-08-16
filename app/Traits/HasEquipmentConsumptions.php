<?php

namespace App\Traits;

use App\Enums\StockMovementType;
use App\Models\EquipmentStockMovement;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasEquipmentConsumptions
{
    public function equipmentConsumptions(): MorphMany
    {
        return $this->morphMany(EquipmentStockMovement::class, 'consumed_for')
            ->where('type', StockMovementType::CONSUMPTION);
    }
}
