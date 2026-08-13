<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Magazyn fizyczny w lokalizacji. Katalog sprzętu jest wspólny;
 * stany (equipment_stocks) i wydania są zawsze w kontekście magazynu.
 *
 * Przyszłe przesunięcia międzymagazynowe (MM) powinny operować na tej samej
 * parze warehouse_id + equipment_variant_id, bez duplikowania katalogu.
 */
class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'name',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(EquipmentStock::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(EquipmentIssue::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(EquipmentStockMovement::class);
    }

    public function getDisplayNameAttribute(): string
    {
        $locationName = $this->location?->name;

        if (filled($locationName) && $locationName !== $this->name) {
            return "{$this->name} ({$locationName})";
        }

        return $this->name;
    }
}
