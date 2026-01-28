<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentIssue extends Model
{
    use HasFactory;

    protected $fillable = [
        'equipment_id',
        'employee_id',
        'project_assignment_id',
        'quantity_issued',
        'issue_date',
        'expected_return_date',
        'actual_return_date',
        'status',
        'notes',
        'issued_by',
        'returned_by',
    ];

    protected $casts = [
        'quantity_issued' => 'integer',
        'issue_date' => 'date',
        'expected_return_date' => 'date',
        'actual_return_date' => 'date',
    ];

    /**
     * Get the equipment that was issued.
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * Get the employee who received the equipment.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the project assignment (if issued for a project).
     */
    public function projectAssignment(): BelongsTo
    {
        return $this->belongsTo(ProjectAssignment::class);
    }

    /**
     * Get the user who issued the equipment.
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * Get the user who returned the equipment.
     */
    public function returner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    /**
     * Mark equipment as returned, damaged, or lost.
     */
    public function markAsReturned(\Carbon\Carbon $returnDate, ?int $returnedBy = null, string $status = 'returned'): void
    {
        $this->update([
            'status' => $status,
            'actual_return_date' => $returnDate,
            'returned_by' => $returnedBy ?? auth()->id(),
        ]);
    }
}
