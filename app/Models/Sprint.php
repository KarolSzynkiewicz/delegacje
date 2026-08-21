<?php

namespace App\Models;

use App\Traits\HasComments;
use App\Traits\HasDateRange;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Sprint extends Model
{
    use HasComments, HasDateRange, HasFactory;

    protected $fillable = [
        'name',
        'goal',
        'definition_of_done',
        'start_date',
        'end_date',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Sprint $sprint) {
            $sprint->attachments->each->delete();
            $sprint->comments->each->delete();
        });
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function orderedTasks(): HasMany
    {
        return $this->tasks()
            ->orderByRaw('sprint_position IS NULL')
            ->orderBy('sprint_position')
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->orderBy('id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(SprintMilestone::class)
            ->orderBy('position')
            ->orderBy('due_date')
            ->orderBy('id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function nextTaskPosition(): int
    {
        return (int) $this->tasks()->max('sprint_position') + 1;
    }

    public function nextMilestonePosition(): int
    {
        return (int) $this->milestones()->max('position') + 1;
    }

    public function label(): string
    {
        if (! $this->start_date || ! $this->end_date) {
            return $this->name;
        }

        return $this->name.' · '.$this->start_date->format('d.m').'–'.$this->end_date->format('d.m.Y');
    }

    public function statusLabel(): string
    {
        if ($this->isCurrentlyActive()) {
            return 'Trwa';
        }

        if ($this->isScheduled()) {
            return 'Nadchodzący';
        }

        return 'Zakończony';
    }
}
