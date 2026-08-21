<?php

namespace App\Models;

use App\Enums\WorkItemStatus;
use App\Enums\WorkItemType;
use App\Services\WorkItemSync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WorkItem extends Model
{
    protected $fillable = [
        'type',
        'source_type',
        'source_id',
        'title',
        'status',
        'assignee_id',
        'sprint_id',
        'due_at',
    ];

    protected $casts = [
        'type' => WorkItemType::class,
        'status' => WorkItemStatus::class,
        'due_at' => 'date',
    ];

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    public function openUrl(): string
    {
        return app(WorkItemSync::class)->url($this);
    }

    public function canCompleteInline(): bool
    {
        return app(WorkItemSync::class)->canComplete($this);
    }

    public function complete(): void
    {
        app(WorkItemSync::class)->complete($this);
    }

    public function reopen(): void
    {
        app(WorkItemSync::class)->reopen($this);
    }
}
