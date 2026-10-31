<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasDateRange;
use Carbon\Carbon;

class EmployeeRate extends Model
{
    use HasFactory, HasDateRange;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'amount',
        'currency',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the employee for this rate.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Compact hourly label for dense UI (e.g. 13e, 12,5e, 55zł).
     */
    public function compactLabel(): string
    {
        return self::formatCompactLabel((float) $this->amount, (string) $this->currency);
    }

    public static function formatCompactLabel(float $amount, string $currency): string
    {
        $formatted = fmod(abs($amount), 1.0) < 0.001
            ? (string) (int) round($amount)
            : rtrim(rtrim(number_format($amount, 2, ',', ''), '0'), ',');

        $suffix = match (strtoupper($currency)) {
            'EUR' => 'e',
            'PLN' => 'zł',
            'USD' => '$',
            default => ' '.strtoupper($currency),
        };

        return $formatted.$suffix;
    }

    public function coversDate(Carbon|string $date): bool
    {
        $day = $date instanceof Carbon ? $date->copy()->startOfDay() : Carbon::parse($date)->startOfDay();
        $start = $this->start_date?->copy()->startOfDay();
        $end = $this->end_date?->copy()->startOfDay();

        if ($start === null || $start->gt($day)) {
            return false;
        }

        return $end === null || $end->gte($day);
    }

    /**
     * Scope: Filter rates that are active (date-based).
     * Uses HasDateRange::scopeActive() which filters by dates only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $this->scopeActiveAtDate($query, Carbon::today());
    }
}
