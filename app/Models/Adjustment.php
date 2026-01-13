<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Adjustment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'payroll_id',
        'amount',
        'currency',
        'type',
        'date',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
    ];

    /**
     * Get the employee for this adjustment.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the payroll for this adjustment.
     */
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    /**
     * Get the effective amount for payroll calculation.
     * Penalties are negative, bonuses are positive.
     * 
     * @return float
     */
    public function getEffectiveAmount(): float
    {
        if ($this->type === 'penalty') {
            return -abs((float) $this->amount);
        }
        
        return (float) $this->amount;
    }

    /**
     * Scope a query to only include adjustments in a date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include adjustments for a specific employee.
     */
    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope a query to only include adjustments not linked to any payroll.
     */
    public function scopeUnlinked($query)
    {
        return $query->whereNull('payroll_id');
    }

    /**
     * Scope a query to include adjustments linked to a specific payroll or unlinked.
     */
    public function scopeForPayrollRecalculation($query, Payroll $payroll)
    {
        return $query->where(function ($q) use ($payroll) {
            $q->whereNull('payroll_id')
              ->orWhere('payroll_id', $payroll->id);
        });
    }
}
