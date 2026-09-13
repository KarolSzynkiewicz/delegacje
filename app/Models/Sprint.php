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

    public function readinessItems(): HasMany
    {
        return $this->hasMany(SprintReadinessItem::class)
            ->orderBy('position')
            ->orderBy('id');
    }

    public function doneItems(): HasMany
    {
        return $this->hasMany(SprintDodItem::class)
            ->orderBy('position')
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

    public function nextReadinessPosition(): int
    {
        return (int) $this->readinessItems()->max('position') + 1;
    }

    public function nextDonePosition(): int
    {
        return (int) $this->doneItems()->max('position') + 1;
    }

    /**
     * @return array{
     *     readiness: list<array{id: int, name: string, done: bool}>,
     *     done: list<array{id: int, name: string, done: bool}>,
     *     milestones: list<array{id: int, name: string, due_date: string|null, completed: bool}>
     * }
     */
    public function checklists(): array
    {
        $this->loadMissing(['readinessItems', 'doneItems', 'milestones']);

        return [
            'readiness' => $this->mapChecklist($this->readinessItems),
            'done' => $this->mapChecklist($this->doneItems),
            'milestones' => $this->milestones
                ->map(fn (SprintMilestone $milestone) => [
                    'id' => $milestone->id,
                    'name' => $milestone->name,
                    'due_date' => $milestone->due_date?->toDateString(),
                    'completed' => $milestone->isCompleted(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, SprintReadinessItem|SprintDodItem>  $items
     * @return list<array{id: int, name: string, done: bool}>
     */
    private function mapChecklist($items): array
    {
        return $items
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'done' => $item->isCompleted(),
            ])
            ->values()
            ->all();
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
