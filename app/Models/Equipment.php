<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Equipment extends Model
{
    use HasFactory;

    protected $table = 'equipment';

    protected $fillable = [
        'name',
        'description',
        'category',
        'quantity_in_stock',
        'min_quantity',
        'unit',
        'unit_cost',
        'returnable',
        'notes',
    ];

    protected $casts = [
        'quantity_in_stock' => 'integer',
        'min_quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'returnable' => 'boolean',
    ];

    /**
     * Get equipment requirements (which roles need this equipment).
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(EquipmentRequirement::class);
    }

    /**
     * Get equipment issues (who has this equipment).
     */
    public function issues(): HasMany
    {
        return $this->hasMany(EquipmentIssue::class);
    }

    /**
     * Get roles that require this equipment.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'equipment_requirements')
            ->withPivot('required_quantity', 'is_mandatory', 'notes')
            ->withTimestamps();
    }

    /**
     * Get available quantity (in stock - issued).
     */
    public function getAvailableQuantityAttribute(): int
    {
        $issued = $this->issues()
            ->where('status', 'issued')
            ->sum('quantity_issued');

        return max(0, $this->quantity_in_stock - $issued);
    }

    /**
     * Check if equipment is low in stock.
     */
    public function isLowStock(): bool
    {
        return $this->available_quantity <= $this->min_quantity;
    }
}
