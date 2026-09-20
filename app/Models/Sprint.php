<?php

namespace App\Models;

use App\Enums\TaskStatus;
use App\Traits\HasComments;
use App\Traits\HasDateRange;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

class Sprint extends Model
{
    use HasComments, HasDateRange, HasFactory;

    public const BOARD_ACTIVE = 'active';

    public const BOARD_UPCOMING = 'upcoming';

    public const BOARD_LATER = 'later';

    public const BOARD_CLOSED = 'closed';

    /** @var list<string> */
    public const BOARD_STATUSES = [
        self::BOARD_ACTIVE,
        self::BOARD_UPCOMING,
        self::BOARD_LATER,
        self::BOARD_CLOSED,
    ];

    protected $fillable = [
        'name',
        'goal',
        'start_date',
        'end_date',
        'closed_at',
        'parked_at',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
        'parked_at' => 'datetime',
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

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    public function isParked(): bool
    {
        return $this->parked_at !== null && ! $this->isClosed();
    }

    /**
     * Status na liście / w filtrze. „Po terminie” to nadal Aktywny — aż ktoś zamknie.
     */
    public function boardStatus(): string
    {
        if ($this->isClosed()) {
            return self::BOARD_CLOSED;
        }

        if ($this->parked_at !== null) {
            return self::BOARD_LATER;
        }

        if ($this->isScheduled()) {
            return self::BOARD_UPCOMING;
        }

        return self::BOARD_ACTIVE;
    }

    public function statusLabel(): string
    {
        return match ($this->boardStatus()) {
            self::BOARD_CLOSED => 'Zakończony',
            self::BOARD_LATER => 'Później',
            self::BOARD_UPCOMING => 'Nadchodzący',
            default => $this->isPast() ? 'Po terminie' : 'Aktywny',
        };
    }

    public function statusVariant(): string
    {
        return match ($this->boardStatus()) {
            self::BOARD_CLOSED => 'secondary',
            self::BOARD_LATER => 'warning',
            self::BOARD_UPCOMING => 'info',
            default => $this->isPast() ? 'warning' : 'success',
        };
    }

    /**
     * Wizualnie jak status zadania w backlogu (klepsydra / play / ptaszek / krzyżyk).
     *
     * @return array{cls: string, icon: string}
     */
    public function statusChip(): array
    {
        return match ($this->boardStatus()) {
            self::BOARD_CLOSED => ['cls' => 's-completed', 'icon' => '✓'],
            self::BOARD_LATER => ['cls' => 's-cancelled', 'icon' => '✗'],
            self::BOARD_UPCOMING => ['cls' => 's-pending', 'icon' => '⏳'],
            default => $this->isPast()
                ? ['cls' => 's-pending', 'icon' => '⏳']
                : ['cls' => 's-in_progress', 'icon' => '▶'],
        };
    }

    /**
     * @param  Builder<Sprint>  $query
     * @param  list<string>  $statuses
     */
    public function scopeBoardStatus(Builder $query, array $statuses): void
    {
        $statuses = array_values(array_intersect(self::BOARD_STATUSES, $statuses));
        if ($statuses === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $outer) use ($statuses) {
            foreach ($statuses as $status) {
                $outer->orWhere(function (Builder $inner) use ($status) {
                    match ($status) {
                        self::BOARD_CLOSED => $inner->whereNotNull('closed_at'),
                        self::BOARD_LATER => $inner->whereNull('closed_at')->whereNotNull('parked_at'),
                        self::BOARD_UPCOMING => $inner->whereNull('closed_at')->whereNull('parked_at')
                            ->whereDate('start_date', '>', now()->toDateString()),
                        default => $inner->whereNull('closed_at')->whereNull('parked_at')
                            ->whereDate('start_date', '<=', now()->toDateString()),
                    };
                });
            }
        });
    }

    public function openTasksCount(): int
    {
        return $this->tasks
            ->filter(fn (ProjectTask $task) => in_array($task->status, [TaskStatus::PENDING, TaskStatus::IN_PROGRESS], true))
            ->count();
    }

    /**
     * @return array{done: int, scope: int, cancelled: int}
     */
    public function progressCounts(): array
    {
        $tasks = $this->relationLoaded('tasks') ? $this->tasks : $this->tasks()->get();
        $cancelled = $tasks->filter(fn (ProjectTask $task) => $task->status === TaskStatus::CANCELLED)->count();
        $inScope = $tasks->filter(fn (ProjectTask $task) => $task->status !== TaskStatus::CANCELLED);
        $done = $inScope->filter(fn (ProjectTask $task) => $task->status === TaskStatus::COMPLETED)->count();

        return [
            'done' => $done,
            'scope' => $inScope->count(),
            'cancelled' => $cancelled,
        ];
    }

    /**
     * @return Collection<int, User>
     */
    public function participants(): Collection
    {
        $tasks = $this->relationLoaded('tasks') ? $this->tasks : $this->tasks()->with('assignedTo')->get();

        return $tasks
            ->map(fn (ProjectTask $task) => $task->assignedTo)
            ->filter()
            ->unique('id')
            ->values();
    }

    /**
     * @return list<string>
     */
    public function categoryLabels(): array
    {
        $tasks = $this->relationLoaded('tasks') ? $this->tasks : $this->tasks()->get();

        return $tasks
            ->pluck('category')
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn (string $value) => trim($value))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array{done: int, total: int}
     */
    public function checklistCounts(string $relation): array
    {
        $items = $this->relationLoaded($relation) ? $this->{$relation} : $this->{$relation}()->get();
        $total = $items->count();
        $done = $items->filter(fn ($item) => $item->isCompleted())->count();

        return ['done' => $done, 'total' => $total];
    }

    /**
     * Średnia z 4 równych wymiarów: DoR, DoD, praca, kamienie.
     * 0/0 liczy się jako 0% — pusty wymiar ciągnie średnią w dół.
     *
     * @return array{
     *     percent: int,
     *     gradient: string,
     *     metrics: list<array{key: string, label: string, icon: string, color: string, done: int, total: int, pct: int}>
     * }
     */
    public function completionSnapshot(): array
    {
        $work = $this->progressCounts();
        $dor = $this->checklistCounts('readinessItems');
        $dod = $this->checklistCounts('doneItems');
        $milestones = $this->checklistCounts('milestones');

        $metrics = [
            $this->snapshotMetric('dor', 'DoR', 'play-circle', '#a855f7', $dor['done'], $dor['total']),
            $this->snapshotMetric('dod', 'DoD', 'flag', '#3b82f6', $dod['done'], $dod['total']),
            $this->snapshotMetric('work', 'Praca', 'briefcase', '#10b981', $work['done'], $work['scope']),
            $this->snapshotMetric('milestones', 'Kamienie', 'diamond', '#f59e0b', $milestones['done'], $milestones['total']),
        ];

        $percent = intdiv((int) array_sum(array_column($metrics, 'pct')), 4);

        return [
            'percent' => $percent,
            'gradient' => $this->snapshotGradient($metrics),
            'metrics' => $metrics,
        ];
    }

    /**
     * @return array{key: string, label: string, icon: string, color: string, done: int, total: int, pct: int}
     */
    private function snapshotMetric(string $key, string $label, string $icon, string $color, int $done, int $total): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'icon' => $icon,
            'color' => $color,
            'done' => $done,
            'total' => $total,
            'pct' => $total > 0 ? (int) round($done / $total * 100) : 0,
        ];
    }

    /**
     * @param  list<array{color: string, pct: int}>  $metrics
     */
    private function snapshotGradient(array $metrics): string
    {
        $stops = [];
        $cursor = 0.0;
        $slice = 90.0;
        $gap = 2.0;

        foreach ($metrics as $metric) {
            $fill = ($slice - $gap) * ($metric['pct'] / 100);
            $track = $this->hexRgba($metric['color'], 0.18);
            $endFill = $cursor + $fill;
            $endSlice = $cursor + $slice - $gap;
            $stops[] = $metric['color'].' '.$cursor.'deg '.$endFill.'deg';
            $stops[] = $track.' '.$endFill.'deg '.$endSlice.'deg';
            $stops[] = 'transparent '.$endSlice.'deg '.($cursor + $slice).'deg';
            $cursor += $slice;
        }

        return 'conic-gradient(from -90deg, '.implode(', ', $stops).')';
    }

    private function hexRgba(string $hex, float $alpha): string
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return 'rgba('.$r.','.$g.','.$b.','.$alpha.')';
    }
}
