<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentStockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'equipment_id',
        'equipment_variant_id',
        'type',
        'quantity',
        'employee_id',
        'notes',
        'batch_id',
        'created_by',
    ];

    protected $casts = [
        'type' => StockMovementType::class,
        'quantity' => 'integer',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(EquipmentVariant::class, 'equipment_variant_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault([
            'name' => '—',
        ]);
    }
}
