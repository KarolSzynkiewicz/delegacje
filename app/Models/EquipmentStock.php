<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stan konkretnego SKU (wariantu) w konkretnym magazynie.
 */
class EquipmentStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'equipment_variant_id',
        'quantity_in_stock',
        'min_quantity',
    ];

    protected $casts = [
        'quantity_in_stock' => 'integer',
        'min_quantity' => 'integer',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(EquipmentVariant::class, 'equipment_variant_id');
    }
}
