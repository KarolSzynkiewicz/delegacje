<?php

namespace App\Models;

use App\Enums\ApprovalDecision;
use App\Enums\WorkItemStatus;
use App\Enums\WorkItemTimeBlockKind;
use App\Enums\WorkItemType;
use App\Services\WorkItemPlanService;
use App\Services\WorkItemSync;
use App\WorkItems\GridField;
use App\WorkItems\HandlesWorkItem;
use App\WorkItems\StatusWidget;
use App\WorkItems\WorkItemCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class WorkItem extends Model
{
    protected $fillable = [
        'type',
        'source_type',
        'source_id',
        'title',
        'category',
        'priority',
        'status',
        'assignee_id',
        'created_by_id',
        'sprint_id',
        'due_at',
    ];

    protected $casts = [
        'type' => WorkItemType::class,
        'status' => WorkItemStatus::class,
        'due_at' => 'date',
        'priority' => 'integer',
    ];

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    public function timeBlocks(): HasMany
    {
        return $this->hasMany(WorkItemTimeBlock::class);
    }

    public function sessionBlocks(): BelongsToMany
    {
        return $this->belongsToMany(WorkItemTimeBlock::class, 'work_item_time_block_items')
            ->withTimestamps();
    }

    /**
     * @return list<string>
     */
    public function schedulePills(): array
    {
        if ($this->type === WorkItemType::Meeting) {
            $source = $this->source;
            if ($source instanceof ProjectTask && $source->starts_at) {
                $label = $source->starts_at->format('d.m H:i');
                if ($source->ends_at) {
                    $label .= '–'.$source->ends_at->format('H:i');
                }

                return [$label];
            }

            return [];
        }

        return $this->itemScheduleBlocks()
            ->sortBy('starts_at')
            ->values()
            ->map(fn (WorkItemTimeBlock $block) => $block->label())
            ->all();
    }

    /**
     * none | scheduled | stale (otwarte WI, wszystkie sloty przed dniem dzisiejszym).
     */
    public function scheduleState(): string
    {
        $today = now()->startOfDay();

        if ($this->isMeetingItem()) {
            $source = $this->source;
            if (! ($source instanceof ProjectTask) || ! $source->starts_at) {
                return 'none';
            }
            if ($this->status->isOpen() && $source->starts_at->lt($today)) {
                return 'stale';
            }

            return 'scheduled';
        }

        $blocks = $this->itemScheduleBlocks();
        if ($blocks->isEmpty()) {
            return 'none';
        }
        $hasUpcoming = $blocks->contains(
            fn (WorkItemTimeBlock $block) => $block->starts_at->gte($today)
        );
        if ($hasUpcoming) {
            return 'scheduled';
        }

        return $this->status->isOpen() ? 'stale' : 'scheduled';
    }

    public function scheduleLabel(): string
    {
        return match ($this->scheduleState()) {
            'stale' => 'skisło',
            'scheduled' => implode(' · ', $this->schedulePills()) ?: 'w planie',
            default => '—',
        };
    }

    public function scheduleSlotCount(): int
    {
        if ($this->isMeetingItem()) {
            $source = $this->source;

            return ($source instanceof ProjectTask && $source->starts_at) ? 1 : 0;
        }

        return $this->itemScheduleBlocks()->count();
    }

    public function scheduleChipLabel(): string
    {
        if ($this->scheduleState() === 'none') {
            return 'Brak';
        }

        return $this->scheduleChipDetail();
    }

    public function scheduleHoverTip(): string
    {
        $pills = $this->schedulePills();

        return match ($this->scheduleState()) {
            'stale' => $pills === []
                ? 'Slot przed dniem dzisiejszym — otwórz plan'
                : 'Zaległe: '.implode(' · ', $pills),
            'scheduled' => $pills === []
                ? 'Otwórz plan przy tym slocie'
                : implode(' · ', $pills),
            default => 'Zaplanuj w kalendarzu',
        };
    }

    protected function scheduleChipDetail(): string
    {
        $count = $this->scheduleSlotCount();
        if ($count === 1) {
            return $this->schedulePills()[0] ?? $this->polishSlotWord(1);
        }

        return $this->polishSlotWord($count);
    }

    protected function polishSlotWord(int $count): string
    {
        $mod10 = $count % 10;
        $mod100 = $count % 100;
        if ($count === 1) {
            return '1 slot';
        }
        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
            return $count.' sloty';
        }

        return $count.' slotów';
    }

    public function scopeWhereScheduleState(Builder $query, string $state): Builder
    {
        return match ($state) {
            'none' => $query->where(fn (Builder $q) => static::constrainUnscheduled($q)),
            'stale' => $query->where(fn (Builder $q) => static::constrainStale($q)),
            'scheduled' => $query->where(fn (Builder $q) => static::constrainScheduled($q)),
            default => $query->whereRaw('0 = 1'),
        };
    }

    public function scopeWhereNotScheduleState(Builder $query, string $state): Builder
    {
        $others = array_values(array_diff(['none', 'scheduled', 'stale'], [$state]));
        if ($others === [] || ! in_array($state, ['none', 'scheduled', 'stale'], true)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($others) {
            foreach ($others as $index => $other) {
                $index === 0
                    ? $q->where(fn (Builder $inner) => $inner->whereScheduleState($other))
                    : $q->orWhere(fn (Builder $inner) => $inner->whereScheduleState($other));
            }
        });
    }

    /** @return list<string> */
    protected static function openScheduleStatusValues(): array
    {
        return [
            WorkItemStatus::Pending->value,
            WorkItemStatus::InProgress->value,
        ];
    }

    protected static function constrainUnscheduled(Builder $q): void
    {
        $q->where(function (Builder $inner) {
            $inner->where(function (Builder $meeting) {
                $meeting->where('type', WorkItemType::Meeting)
                    ->whereDoesntHaveMorph('source', [ProjectTask::class], function (Builder $source) {
                        $source->whereNotNull('starts_at');
                    });
            })->orWhere(function (Builder $other) {
                $other->where('type', '!=', WorkItemType::Meeting)
                    ->whereDoesntHave('timeBlocks', function (Builder $blocks) {
                        $blocks->where('kind', WorkItemTimeBlockKind::Item);
                    });
            });
        });
    }

    protected static function constrainStale(Builder $q): void
    {
        $today = now()->startOfDay();
        $q->whereIn('status', static::openScheduleStatusValues())
            ->where(function (Builder $inner) use ($today) {
                $inner->where(function (Builder $meeting) use ($today) {
                    $meeting->where('type', WorkItemType::Meeting)
                        ->whereHasMorph('source', [ProjectTask::class], function (Builder $source) use ($today) {
                            $source->whereNotNull('starts_at')->where('starts_at', '<', $today);
                        });
                })->orWhere(function (Builder $other) use ($today) {
                    $other->where('type', '!=', WorkItemType::Meeting)
                        ->whereHas('timeBlocks', function (Builder $blocks) {
                            $blocks->where('kind', WorkItemTimeBlockKind::Item);
                        })
                        ->whereDoesntHave('timeBlocks', function (Builder $blocks) use ($today) {
                            $blocks->where('kind', WorkItemTimeBlockKind::Item)
                                ->where('starts_at', '>=', $today);
                        });
                });
            });
    }

    protected static function constrainScheduled(Builder $q): void
    {
        $today = now()->startOfDay();
        $open = static::openScheduleStatusValues();
        $q->where(function (Builder $inner) use ($today, $open) {
            $inner->where(function (Builder $meeting) use ($today) {
                $meeting->where('type', WorkItemType::Meeting)
                    ->whereHasMorph('source', [ProjectTask::class], function (Builder $source) use ($today) {
                        $source->whereNotNull('starts_at')->where('starts_at', '>=', $today);
                    });
            })->orWhere(function (Builder $closedMeeting) use ($open) {
                $closedMeeting->where('type', WorkItemType::Meeting)
                    ->whereNotIn('status', $open)
                    ->whereHasMorph('source', [ProjectTask::class], function (Builder $source) {
                        $source->whereNotNull('starts_at');
                    });
            })->orWhere(function (Builder $upcoming) use ($today) {
                $upcoming->where('type', '!=', WorkItemType::Meeting)
                    ->whereHas('timeBlocks', function (Builder $blocks) use ($today) {
                        $blocks->where('kind', WorkItemTimeBlockKind::Item)
                            ->where('starts_at', '>=', $today);
                    });
            })->orWhere(function (Builder $closedPast) use ($open) {
                $closedPast->where('type', '!=', WorkItemType::Meeting)
                    ->whereNotIn('status', $open)
                    ->whereHas('timeBlocks', function (Builder $blocks) {
                        $blocks->where('kind', WorkItemTimeBlockKind::Item);
                    });
            });
        });
    }

    public function planPinUrl(): string
    {
        $params = array_filter([
            'u' => $this->assignee_id,
            'pin' => $this->id,
        ]);
        $at = $this->scheduleAnchor();
        if ($at) {
            $params['w'] = app(WorkItemPlanService::class)->weekStart($at)->toDateString();
        }

        return route('work-items.plan', $params);
    }

    public static function forProjectTask(ProjectTask $task): ?self
    {
        $match = static::query()
            ->with(['timeBlocks', 'source'])
            ->where('source_type', $task->getMorphClass())
            ->where('source_id', $task->id)
            ->first();
        if ($match) {
            return $match;
        }
        if (! $task->procedure_run_id) {
            return null;
        }

        return static::query()
            ->with(['timeBlocks', 'source'])
            ->where('source_type', (new ProcedureRun)->getMorphClass())
            ->where('source_id', $task->procedure_run_id)
            ->first();
    }

    /**
     * @return Collection<int, WorkItemTimeBlock>
     */
    protected function itemScheduleBlocks(): Collection
    {
        $blocks = $this->relationLoaded('timeBlocks')
            ? $this->timeBlocks
            : $this->timeBlocks()->get();

        return $blocks
            ->filter(fn (WorkItemTimeBlock $block) => $block->kind === WorkItemTimeBlockKind::Item)
            ->values();
    }

    protected function scheduleAnchor(): mixed
    {
        if ($this->isMeetingItem()) {
            $source = $this->source;
            if ($source instanceof ProjectTask && $source->starts_at) {
                return $source->starts_at;
            }
        }

        return $this->itemScheduleBlocks()->sortByDesc('starts_at')->first()?->starts_at;
    }

    public function isMeetingItem(): bool
    {
        return $this->type === WorkItemType::Meeting;
    }

    public function handler(): HandlesWorkItem
    {
        return WorkItemCatalog::handler($this->type);
    }

    public function supports(GridField|string $field): bool
    {
        $gridField = $field instanceof GridField ? $field : GridField::tryFrom($field);

        return $gridField ? $this->handler()->supports($gridField) : false;
    }

    public function writable(GridField|string $field): bool
    {
        $gridField = $field instanceof GridField ? $field : GridField::tryFrom($field);

        return $gridField ? $this->handler()->writable($gridField) : false;
    }

    public function statusWidget(): StatusWidget
    {
        return $this->handler()->statusWidget();
    }

    public function statusLabel(): string
    {
        return $this->handler()->statusLabel($this);
    }

    public function approvalDecision(): ?ApprovalDecision
    {
        if ($this->type !== WorkItemType::Approval) {
            return null;
        }

        $source = $this->source;

        return $source instanceof ApprovalRequest ? $source->decision : null;
    }

    public function expandable(): bool
    {
        return $this->handler()->expandable($this);
    }

    public function relocatable(GridField|string $field): bool
    {
        $gridField = $field instanceof GridField ? $field : GridField::tryFrom($field);

        return $gridField ? $this->handler()->relocatable($gridField) : false;
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['title'] ?? '');
    }

    public function getDueDateAttribute(): mixed
    {
        return $this->due_at;
    }

    public function getCommentsCountAttribute(): int
    {
        $task = $this->source instanceof ProjectTask ? $this->source : $this->editableProjectTask();

        return (int) ($task?->comments_count ?? 0);
    }

    public function getSubtasksAttribute(): Collection
    {
        $task = $this->source instanceof ProjectTask ? $this->source : null;
        if (! $task) {
            return collect();
        }

        $task->loadMissing('subtasks');

        return $task->subtasks;
    }

    public function editableProjectTask(): ?ProjectTask
    {
        $source = $this->source;

        return match (true) {
            $source instanceof ProjectTask => $source,
            $source instanceof ProcedureRun => $source->task,
            $source instanceof WarehouseDispatch => $source->tasks->first(),
            default => null,
        };
    }

    public function sourceSubtask(): ?TaskSubtask
    {
        return $this->source instanceof TaskSubtask ? $this->source : null;
    }

    public function openUrl(): string
    {
        return app(WorkItemSync::class)->url($this);
    }

    public function sourceCard(): ?array
    {
        if (in_array($this->type, [WorkItemType::Task, WorkItemType::Callback, WorkItemType::Meeting], true)) {
            $source = $this->source;

            return $source instanceof ProjectTask ? $source->sourceCard() : null;
        }

        if ($this->type === WorkItemType::FollowUp && $this->source instanceof CommentMention) {
            $this->source->loadMissing('comment.commentable');

            return $this->source->comment?->commentableCard();
        }

        if ($this->type === WorkItemType::Approval && $this->source instanceof ApprovalRequest) {
            $this->source->loadMissing('comment.commentable');

            return $this->source->comment?->commentableCard();
        }

        return [
            'url' => $this->openUrl(),
            'label' => $this->type->label(),
            'icon' => $this->type->icon(),
        ];
    }

    public function plainDescription(): string
    {
        $source = $this->source;
        if ($source instanceof ProjectTask) {
            return $source->plainDescription();
        }
        if ($source instanceof CommentMention) {
            $source->loadMissing('comment');

            return trim((string) ($source->comment?->body ?? ''));
        }
        if ($source instanceof ApprovalRequest) {
            return trim((string) ($source->description ?? ''));
        }
        if ($source instanceof TaskSubtask && $source->task) {
            return 'W zadaniu: '.$source->task->name;
        }

        return '';
    }
}
