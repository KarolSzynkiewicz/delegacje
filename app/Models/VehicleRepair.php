<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\ServiceActionType;
use Carbon\Carbon;

class VehicleRepair extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'location_id',
        'action_type',
        'start_date',
        'end_date',
        'price',
        'currency',
        'notes',
        'fixed_cost_entry_id',
        'previous_technical_condition',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'price' => 'decimal:2',
        'action_type' => ServiceActionType::class,
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function fixedCostEntry(): BelongsTo
    {
        return $this->belongsTo(FixedCostEntry::class);
    }

    /**
     * Computed status based on dates.
     * - completed: end_date is set
     * - in_progress: start_date <= today, no end_date
     * - created: start_date > today
     */
    public function getStatusAttribute(): string
    {
        if ($this->end_date !== null) {
            return 'completed';
        }

        $today = Carbon::today();

        if ($this->start_date->lte($today)) {
            return 'in_progress';
        }

        return 'created';
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'completed'   => 'Zakończona',
            'in_progress' => 'W trakcie',
            'created'     => 'Zaplanowana',
            default       => 'Nieznany',
        };
    }

    public function getStatusBadgeVariantAttribute(): string
    {
        return match($this->status) {
            'completed'   => 'success',
            'in_progress' => 'warning',
            'created'     => 'info',
            default       => 'secondary',
        };
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function scopeForVehicle($query, int $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('end_date');
    }

    public function scopeOpen($query)
    {
        return $query->whereNull('end_date');
    }
}
