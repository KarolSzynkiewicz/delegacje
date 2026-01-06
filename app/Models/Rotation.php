<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Rotation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'status', // Tylko dla 'cancelled' - reszta jest automatyczna
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the employee that owns this rotation.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the computed status based on dates.
     * Status: 'scheduled' (zaplanowana), 'active' (aktywna), 'completed' (zakończona)
     */
    public function getStatusAttribute($value): string
    {
        // Jeśli status jest ustawiony na 'cancelled', zwróć go (anulowane ręcznie)
        if ($value === 'cancelled') {
            return 'cancelled';
        }

        // Jeśli brak dat, zwróć wartość z bazy lub 'scheduled'
        if (!$this->start_date || !$this->end_date) {
            return $value ?? 'scheduled';
        }

        $today = now()->startOfDay();
        $startDate = $this->start_date->startOfDay();
        $endDate = $this->end_date->startOfDay();

        if ($startDate->isFuture()) {
            return 'scheduled'; // Zaplanowana - jeszcze się nie rozpoczęła
        } elseif ($endDate->isPast()) {
            return 'completed'; // Zakończona - już minęła
        } else {
            return 'active'; // Aktywna - trwa obecnie
        }
    }

    /**
     * Scope a query to only include active rotations (based on dates).
     */
    public function scopeActive(Builder $query): Builder
    {
        $today = now()->toDateString();
        return $query->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->where(function ($q) {
                $q->whereNull('status')
                  ->orWhere('status', '!=', 'cancelled');
            });
    }

    /**
     * Scope a query to only include scheduled rotations.
     */
    public function scopeScheduled(Builder $query): Builder
    {
        $today = now()->toDateString();
        return $query->whereDate('start_date', '>', $today)
            ->where(function ($q) {
                $q->whereNull('status')
                  ->orWhere('status', '!=', 'cancelled');
            });
    }

    /**
     * Scope a query to only include completed rotations.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        $today = now()->toDateString();
        return $query->whereDate('end_date', '<', $today)
            ->where(function ($q) {
                $q->whereNull('status')
                  ->orWhere('status', '!=', 'cancelled');
            });
    }

    /**
     * Scope a query to only include rotations within a date range.
     */
    public function scopeInDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });
    }
}
