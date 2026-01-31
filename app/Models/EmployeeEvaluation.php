<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeEvaluation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'created_by',
        'engagement',
        'skills',
        'orderliness',
        'behavior',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'engagement' => 'integer',
        'skills' => 'integer',
        'orderliness' => 'integer',
        'behavior' => 'integer',
    ];

    /**
     * Get the employee that this evaluation belongs to.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who created this evaluation.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the average score of all criteria.
     */
    public function getAverageScoreAttribute(): float
    {
        return round(($this->engagement + $this->skills + $this->orderliness + $this->behavior) / 4, 2);
    }
}
