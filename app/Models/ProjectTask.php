<?php

namespace App\Models;

use App\Enums\TaskStatus;
use App\Traits\HasComments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ProjectTask extends Model
{
    use HasFactory, HasComments;

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'status',
        'assigned_to',
        'due_date',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'status' => TaskStatus::class,
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the project that owns the task.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user assigned to the task.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who created the task.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include pending tasks.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', TaskStatus::PENDING);
    }

    /**
     * Scope a query to only include in progress tasks.
     */
    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', TaskStatus::IN_PROGRESS);
    }

    /**
     * Scope a query to only include completed tasks.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', TaskStatus::COMPLETED);
    }

    /**
     * Scope a query to only include cancelled tasks.
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', TaskStatus::CANCELLED);
    }

    /**
     * Mark task as in progress.
     */
    public function markInProgress(): void
    {
        $this->update([
            'status' => TaskStatus::IN_PROGRESS,
            'completed_at' => null,
        ]);
    }

    /**
     * Mark task as completed.
     */
    public function markCompleted(): void
    {
        $this->update([
            'status' => TaskStatus::COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Cancel the task.
     */
    public function cancel(): void
    {
        $this->update([
            'status' => TaskStatus::CANCELLED,
            'completed_at' => null,
        ]);
    }

    /**
     * Reassign the task to a user.
     */
    public function reassign(?User $user): void
    {
        $this->update([
            'assigned_to' => $user?->id,
        ]);
    }
}
