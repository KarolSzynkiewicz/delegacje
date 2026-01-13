<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\PayrollStatus;
use Carbon\Carbon;

/**
 * Payroll - niemutowalny snapshot rozliczeniowy dla pracownika i okresu.
 * 
 * Zawiera obliczone kwoty na podstawie TimeLogów i EmployeeRate z momentu generowania.
 * Po utworzeniu nie powinien być modyfikowany (tylko status i adjustments_amount).
 */
class Payroll extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'period_start',
        'period_end',
        'hours_amount',
        'adjustments_amount',
        'total_amount',
        'currency',
        'status',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'hours_amount' => 'decimal:2',
        'adjustments_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'status' => PayrollStatus::class,
    ];

    /**
     * Get the employee for this payroll.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get formatted display name for payroll.
     * Format: "Pracownik | od YYYY-MM-DD do YYYY-MM-DD"
     * 
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        $employeeName = $this->employee ? $this->employee->full_name : 'Nieznany';
        $periodStart = $this->period_start->format('Y-m-d');
        $periodEnd = $this->period_end->format('Y-m-d');
        
        return "{$employeeName} | od {$periodStart} do {$periodEnd}";
    }

    /**
     * Recalculate total_amount based on hours_amount and adjustments_amount.
     * Should be called after updating adjustments_amount.
     */
    public function recalculateTotal(): void
    {
        $this->total_amount = $this->hours_amount + $this->adjustments_amount;
    }

    /**
     * Get all adjustments for this payroll.
     */
    public function adjustments(): HasMany
    {
        return $this->hasMany(Adjustment::class);
    }

    /**
     * Get all advances for this payroll.
     */
    public function advances(): HasMany
    {
        return $this->hasMany(Advance::class);
    }

    /**
     * Scope a query to only include payrolls with a specific status.
     */
    public function scopeWithStatus($query, PayrollStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include payrolls that can be recalculated.
     */
    public function scopeRecalculatable($query)
    {
        return $query->whereIn('status', [PayrollStatus::DRAFT, PayrollStatus::ISSUED]);
    }

    /**
     * Scope a query to only include payrolls that can be deleted.
     */
    public function scopeDeletable($query)
    {
        return $query->where('status', PayrollStatus::DRAFT);
    }

    /**
     * Check if this payroll can be recalculated.
     * 
     * @return bool
     */
    public function canBeRecalculated(): bool
    {
        return in_array($this->status, [PayrollStatus::DRAFT, PayrollStatus::ISSUED]);
    }

    /**
     * Check if this payroll can be deleted.
     * 
     * @return bool
     */
    public function canBeDeleted(): bool
    {
        return $this->status === PayrollStatus::DRAFT;
    }

    /**
     * Check if payroll exists for employee and period (exact match).
     * 
     * @param int $employeeId
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @return bool
     */
    public static function existsForPeriod(int $employeeId, Carbon $periodStart, Carbon $periodEnd): bool
    {
        return static::where('employee_id', $employeeId)
            ->where('period_start', $periodStart->toDateString())
            ->where('period_end', $periodEnd->toDateString())
            ->exists();
    }

    /**
     * Check if a payroll overlaps with existing payrolls for an employee.
     * Excludes a specific payroll (for updates).
     * 
     * @param int $employeeId
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @param int|null $excludePayrollId
     * @return bool
     */
    public static function hasOverlappingPayrolls(
        int $employeeId,
        Carbon $periodStart,
        Carbon $periodEnd,
        ?int $excludePayrollId = null
    ): bool {
        $query = static::where('employee_id', $employeeId)
            ->where(function ($query) use ($periodStart, $periodEnd) {
                // Check if any existing payroll overlaps with the new period
                $query->whereBetween('period_start', [$periodStart->toDateString(), $periodEnd->toDateString()])
                    ->orWhereBetween('period_end', [$periodStart->toDateString(), $periodEnd->toDateString()])
                    ->orWhere(function ($q) use ($periodStart, $periodEnd) {
                        // Existing payroll completely covers the new period
                        $q->where('period_start', '<=', $periodStart->toDateString())
                          ->where('period_end', '>=', $periodEnd->toDateString());
                    });
            });

        if ($excludePayrollId) {
            $query->where('id', '!=', $excludePayrollId);
        }

        return $query->exists();
    }
}
