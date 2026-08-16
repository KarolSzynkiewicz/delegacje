<?php

namespace App\Models;

use App\Enums\Currency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipment extends Model
{
    use HasFactory;

    protected $table = 'equipment';

    protected $fillable = [
        'name',
        'description',
        'image_path',
        'category',
        'variant_label',
        'unit_cost',
        'currency',
        'issuable',
        'returnable',
        'is_archived',
        'removed_at',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'currency' => Currency::class,
        'issuable' => 'boolean',
        'returnable' => 'boolean',
        'is_archived' => 'boolean',
        'removed_at' => 'datetime',
    ];

    public function variants(): HasMany
    {
        return $this->hasMany(EquipmentVariant::class)->orderBy('sort_order')->orderBy('value');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(EquipmentRequirement::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(EquipmentIssue::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(EquipmentStockMovement::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'equipment_requirements')
            ->withPivot('required_quantity', 'is_mandatory', 'notes')
            ->withTimestamps();
    }

    public function scopeIssuable(Builder $query): Builder
    {
        return $query->where('issuable', true);
    }

    public function scopeNotIssuable(Builder $query): Builder
    {
        return $query->where('issuable', false);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_archived', false)->whereNull('removed_at');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where(function (Builder $archived) {
            $archived->where('is_archived', true)
                ->orWhereNotNull('removed_at');
        });
    }

    public function isArchived(): bool
    {
        return (bool) $this->is_archived || $this->removed_at !== null;
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return asset('storage/'.$this->image_path);
    }

    public function scopeWithWarehouseInventory(Builder $query, Warehouse $warehouse): Builder
    {
        return $query->with(['variants' => function ($variants) use ($warehouse) {
            $variants
                ->with('stocks')
                ->withSum([
                    'issues as reserved_quantity' => function ($issues) use ($warehouse) {
                        $issues->where('warehouse_id', $warehouse->id)
                            ->where('status', EquipmentIssue::STATUS_RESERVED);
                    },
                ], 'quantity_issued')
                ->withSum([
                    'issues as issued_outstanding' => function ($issues) use ($warehouse) {
                        $issues->where('warehouse_id', $warehouse->id)
                            ->where('status', 'issued');
                    },
                ], 'quantity_issued')
                ->withSum([
                    'issues as issued_outstanding_others' => function ($issues) use ($warehouse) {
                        $issues->where('warehouse_id', '!=', $warehouse->id)
                            ->where('status', 'issued');
                    },
                ], 'quantity_issued');
        }]);
    }

    public function hasVariants(): bool
    {
        if (filled($this->variant_label)) {
            return true;
        }

        $variants = $this->relationLoaded('variants')
            ? $this->variants
            : $this->variants()->get();

        return $variants->contains(fn (EquipmentVariant $variant) => filled($variant->value));
    }

    public function quantityIn(Warehouse $warehouse): int
    {
        return (int) $this->variantsForInventory()->sum(
            fn (EquipmentVariant $variant) => $variant->quantityIn($warehouse)
        );
    }

    public function minQuantityIn(Warehouse $warehouse): int
    {
        return (int) $this->variantsForInventory()->sum(
            fn (EquipmentVariant $variant) => $variant->minQuantityIn($warehouse)
        );
    }

    public function availableIn(Warehouse $warehouse): int
    {
        return (int) $this->variantsForInventory()->sum(
            fn (EquipmentVariant $variant) => $variant->availableIn($warehouse)
        );
    }

    public function quantityInOthers(Warehouse $warehouse): int
    {
        return (int) $this->variantsForInventory()->sum(
            fn (EquipmentVariant $variant) => $variant->quantityInOthers($warehouse)
        );
    }

    public function reservedIn(Warehouse $warehouse): int
    {
        return (int) $this->variantsForInventory()->sum(
            fn (EquipmentVariant $variant) => $variant->reservedIn($warehouse)
        );
    }

    public function issuedOutstandingIn(Warehouse $warehouse): int
    {
        return (int) $this->variantsForInventory()->sum(
            fn (EquipmentVariant $variant) => $variant->issuedOutstandingIn($warehouse)
        );
    }

    public function issuedOutstandingInOthers(Warehouse $warehouse): int
    {
        return (int) $this->variantsForInventory()->sum(
            fn (EquipmentVariant $variant) => $variant->issuedOutstandingInOthers($warehouse)
        );
    }

    public function isLowStockIn(Warehouse $warehouse): bool
    {
        $variants = $this->variantsForInventory();

        if ($variants->isEmpty()) {
            return false;
        }

        return $variants->contains(fn (EquipmentVariant $variant) => $variant->isLowStockIn($warehouse));
    }

    /**
     * @return \Illuminate\Support\Collection<int, EquipmentVariant>
     */
    private function variantsForInventory()
    {
        return $this->relationLoaded('variants')
            ? $this->variants
            : $this->variants()->get();
    }
}
