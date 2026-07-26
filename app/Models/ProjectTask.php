<?php

namespace App\Models;

use App\Enums\TaskStatus;
use App\Traits\HasComments;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ProjectTask extends Model
{
    use HasComments, HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (ProjectTask $task) {
            $task->attachments->each->delete();
        });
    }

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'status',
        'priority',
        'category',
        'assigned_to',
        'due_date',
        'completed_at',
        'created_by',
        'procedure_run_id',
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
     * Get the procedure run linked to this task (if any).
     */
    public function procedureRun(): BelongsTo
    {
        return $this->belongsTo(ProcedureRun::class, 'procedure_run_id');
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

    /**
     * Załączniki do zadania (np. specyfikacja, zdjęcia).
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Opis do wyświetlenia w UI: dekoduje encje HTML zapisane w bazie (&gt; → >),
     * żeby Blade {{ }} nie dublował ich do widocznego „&gt;”.
     */
    public function plainDescription(): string
    {
        return html_entity_decode((string) ($this->description ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Get the subtasks for this task.
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(TaskSubtask::class, 'task_id');
    }

    /**
     * Get the progress percentage of completed subtasks.
     */
    public function getSubtasksProgressAttribute(): float
    {
        $total = $this->subtasks()->count();
        if ($total === 0) {
            return 0;
        }

        $completed = $this->subtasks()->where('is_completed', true)->count();

        return round(($completed / $total) * 100, 2);
    }

    /**
     * Mapa id podzadania → numer wyświetlany (#1, #2, …), kolejność: data utworzenia, potem id.
     *
     * @return array<int, int>
     */
    public function subtaskDisplayNumbers(): array
    {
        $this->loadMissing('subtasks');

        $map = [];
        foreach ($this->subtasks->sortBy(['created_at', 'id'])->values() as $index => $subtask) {
            $map[$subtask->id] = $index + 1;
        }

        return $map;
    }
}
