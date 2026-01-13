<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
     * Recalculate total_amount based on hours_amount and adjustments_amount.
     * Should be called after updating adjustments_amount.
     */
    public function recalculateTotal(): void
    {
        $this->total_amount = $this->hours_amount + $this->adjustments_amount;
    }
}
