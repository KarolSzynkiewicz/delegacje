<?php

namespace App\Models;

use App\Enums\VehicleLifecycleEventType;
use App\Enums\VehicleRetirementReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleLifecycleEvent extends Model
{
    protected $fillable = [
        'vehicle_id',
        'type',
        'occurred_at',
        'reason',
        'note',
        'created_by',
    ];

    protected $casts = [
        'type' => VehicleLifecycleEventType::class,
        'occurred_at' => 'datetime',
        'reason' => VehicleRetirementReason::class,
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
