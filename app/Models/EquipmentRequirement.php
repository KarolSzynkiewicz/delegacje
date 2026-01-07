<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_id',
        'equipment_id',
        'required_quantity',
        'is_mandatory',
        'notes',
    ];

    protected $casts = [
        'required_quantity' => 'integer',
        'is_mandatory' => 'boolean',
    ];

    /**
     * Get the role that requires this equipment.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the equipment that is required.
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}
