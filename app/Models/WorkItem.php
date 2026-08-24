<?php

namespace App\Models;

use App\Enums\ApprovalDecision;
use App\Enums\WorkItemStatus;
use App\Enums\WorkItemType;
use App\Services\WorkItemSync;
use App\WorkItems\GridField;
use App\WorkItems\HandlesWorkItem;
use App\WorkItems\StatusWidget;
use App\WorkItems\WorkItemCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        if ($this->type === WorkItemType::Task) {
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
