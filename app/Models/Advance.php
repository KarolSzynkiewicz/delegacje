<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Advance extends Model
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
        'date',
        'is_interest_bearing',
        'interest_rate',
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
        'is_interest_bearing' => 'boolean',
        'interest_rate' => 'decimal:2',
    ];

    /**
     * Get the employee for this advance.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the payroll for this advance.
     */
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    /**
     * Calculate the total amount to deduct (advance + interest if applicable).
     * 
     * @return float
     */
    public function getTotalDeductionAmount(): float
    {
        $total = (float) $this->amount;
        
        if ($this->is_interest_bearing && $this->interest_rate) {
            $interest = $total * ((float) $this->interest_rate / 100);
            $total += $interest;
        }
        
        return round($total, 2);
    }

    /**
     * Calculate interest amount separately.
     * 
     * @return float
     */
    public function getInterestAmount(): float
    {
        if (!$this->is_interest_bearing || !$this->interest_rate) {
            return 0.0;
        }
        
        return round((float) $this->amount * ((float) $this->interest_rate / 100), 2);
    }

    /**
     * Scope a query to only include advances in a date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include advances for a specific employee.
     */
    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope a query to only include advances not linked to any payroll.
     */
    public function scopeUnlinked($query)
    {
        return $query->whereNull('payroll_id');
    }

    /**
     * Scope a query to include advances linked to a specific payroll or unlinked.
     */
    public function scopeForPayrollRecalculation($query, Payroll $payroll)
    {
        return $query->where(function ($q) use ($payroll) {
            $q->whereNull('payroll_id')
              ->orWhere('payroll_id', $payroll->id);
        });
    }
}
