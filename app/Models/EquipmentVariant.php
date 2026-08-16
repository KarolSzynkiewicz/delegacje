<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentVariant extends Model
{
    use HasFactory;

    public const RESERVED_STATUS = 'reserved';

    protected $fillable = [
        'equipment_id',
        'value',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(EquipmentIssue::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(EquipmentStock::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(EquipmentStockMovement::class);
    }

    public function stockIn(Warehouse $warehouse): EquipmentStock
    {
        $stock = $this->relationLoaded('stocks')
            ? $this->stocks->firstWhere('warehouse_id', $warehouse->id)
            : $this->stocks()->where('warehouse_id', $warehouse->id)->first();

        return $stock ?? new EquipmentStock([
            'warehouse_id' => $warehouse->id,
            'equipment_variant_id' => $this->id,
            'quantity_in_stock' => 0,
            'min_quantity' => 0,
        ]);
    }

    public function quantityIn(Warehouse $warehouse): int
    {
        return $this->stockIn($warehouse)->quantity_in_stock;
    }

    public function quantityInOthers(Warehouse $warehouse): int
    {
        $stocks = $this->relationLoaded('stocks')
            ? $this->stocks
            : $this->stocks()->get();

        return (int) $stocks
            ->where('warehouse_id', '!=', $warehouse->id)
            ->sum('quantity_in_stock');
    }

    public function issuedOutstandingIn(Warehouse $warehouse): int
    {
        if (array_key_exists('issued_outstanding', $this->attributes)) {
            return (int) $this->attributes['issued_outstanding'];
        }

        return (int) $this->issues()
            ->where('warehouse_id', $warehouse->id)
            ->where('status', 'issued')
            ->sum('quantity_issued');
    }

    public function issuedOutstandingTotal(): int
    {
        if (array_key_exists('issued_outstanding_total', $this->attributes)) {
            return (int) $this->attributes['issued_outstanding_total'];
        }

        return (int) $this->issues()
            ->where('status', 'issued')
            ->sum('quantity_issued');
    }

    public function minQuantityTotal(): int
    {
        $stocks = $this->relationLoaded('stocks')
            ? $this->stocks
            : $this->stocks()->get();

        return (int) $stocks->sum('min_quantity');
    }

    public function issuedOutstandingInOthers(Warehouse $warehouse): int
    {
        if (array_key_exists('issued_outstanding_others', $this->attributes)) {
            return (int) $this->attributes['issued_outstanding_others'];
        }

        return (int) $this->issues()
            ->where('warehouse_id', '!=', $warehouse->id)
            ->where('status', 'issued')
            ->sum('quantity_issued');
    }

    public function minQuantityIn(Warehouse $warehouse): int
    {
        return $this->stockIn($warehouse)->min_quantity;
    }

    public function reservedIn(Warehouse $warehouse): int
    {
        if (array_key_exists('reserved_quantity', $this->attributes)) {
            return (int) $this->attributes['reserved_quantity'];
        }

        return (int) $this->issues()
            ->where('warehouse_id', $warehouse->id)
            ->where('status', self::RESERVED_STATUS)
            ->sum('quantity_issued');
    }

    public function availableIn(Warehouse $warehouse): int
    {
        return max(0, $this->quantityIn($warehouse) - $this->reservedIn($warehouse));
    }

    public function isLowStockIn(Warehouse $warehouse): bool
    {
        return $this->availableIn($warehouse) <= $this->minQuantityIn($warehouse);
    }

    public function deletionBlockReason(): ?string
    {
        if ($this->issues()->exists()) {
            return 'Istnieją wydania tego rodzaju.';
        }

        if ($this->movements()->exists()) {
            return 'Istnieją rozchody lub inne ruchy stanu tego rodzaju.';
        }

        $hasStock = $this->relationLoaded('stocks')
            ? $this->stocks->contains(fn (EquipmentStock $stock) => (int) $stock->quantity_in_stock > 0)
            : $this->stocks()->where('quantity_in_stock', '>', 0)->exists();

        if ($hasStock) {
            return 'Ten rodzaj ma jeszcze stan w magazynie.';
        }

        return null;
    }

    public function getKindLabelAttribute(): string
    {
        if (filled($this->value)) {
            return (string) $this->value;
        }

        return '—';
    }

    public function getDisplayNameAttribute(): string
    {
        $this->loadMissing('equipment');
        $name = $this->equipment?->name ?? 'Pozycja magazynowa';

        if (! filled($this->value)) {
            return $name;
        }

        $label = $this->equipment?->variant_label;

        if (filled($label)) {
            return "{$name} ({$label}: {$this->value})";
        }

        return "{$name} ({$this->value})";
    }

    /**
     * SKU w WMS: nazwa typu × wariant (np. „Spodnie BHP · M”).
     * Stan magazynowy śledzimy na tej parze, per magazyn.
     */
    public function getSkuAttribute(): string
    {
        $this->loadMissing('equipment');
        $name = $this->equipment?->name ?? 'Pozycja magazynowa';

        if (! filled($this->value)) {
            return $name;
        }

        return $name.' · '.$this->value;
    }
}
