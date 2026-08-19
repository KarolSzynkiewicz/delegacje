<?php

namespace App\Models;

use App\Contracts\TaskSubject;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TaskSubtask extends Model implements TaskSubject
{
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (TaskSubtask $subtask) {
            $subtask->tasks()
                ->whereNotIn('status', [TaskStatus::COMPLETED, TaskStatus::CANCELLED])
                ->get()
                ->each(fn (ProjectTask $task) => $task->cancel());
        });
    }

    protected $fillable = [
        'task_id',
        'sort_order',
        'name',
        'is_completed',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'task_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(TaskSubtaskEvent::class, 'subtask_id')->orderBy('created_at');
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(ProjectTask::class, 'subject');
    }

    public function taskCardUrl(): string
    {
        $this->loadMissing('task');

        return $this->task ? route('tasks.show', $this->task) : url('/');
    }

    public function taskCardLabel(): string
    {
        return 'Podzadanie';
    }

    public function taskCardIcon(): string
    {
        return 'bi-check2-square';
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('is_completed', true);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('is_completed', false);
    }

    public function markCompleted(): void
    {
        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        $this->syncLinkedMentionTasks(true);
    }

    public function markIncomplete(): void
    {
        $this->update([
            'is_completed' => false,
            'completed_at' => null,
        ]);

        $this->syncLinkedMentionTasks(false);
    }

    private function syncLinkedMentionTasks(bool $done): void
    {
        foreach ($this->tasks()->whereNotIn('status', [TaskStatus::CANCELLED])->get() as $task) {
            if ($done && $task->status !== TaskStatus::COMPLETED) {
                $task->update([
                    'status' => TaskStatus::COMPLETED,
                    'completed_at' => now(),
                ]);
            }

            if (! $done && $task->status === TaskStatus::COMPLETED) {
                $task->update([
                    'status' => TaskStatus::PENDING,
                    'completed_at' => null,
                ]);
            }
        }
    }
}
