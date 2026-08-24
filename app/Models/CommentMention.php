<?php

namespace App\Models;

use App\Enums\WorkItemStatus;
use App\Notifications\MentionCompleted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class CommentMention extends Model
{
    protected $fillable = [
        'comment_id',
        'assigned_to',
        'created_by',
        'title',
        'status',
    ];

    protected $casts = [
        'status' => WorkItemStatus::class,
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function workItem(): MorphOne
    {
        return $this->morphOne(WorkItem::class, 'source');
    }

    public function isCompleted(): bool
    {
        return $this->status === WorkItemStatus::Completed;
    }

    public function markCompleted(): void
    {
        if ($this->isCompleted()) {
            return;
        }

        $this->update(['status' => WorkItemStatus::Completed]);

        $this->loadMissing('createdBy');
        $creator = $this->createdBy;
        $actor = auth()->user();
        if (! $creator) {
            return;
        }
        if ($actor && (int) $creator->id === (int) $actor->id) {
            return;
        }

        $creator->notify(new MentionCompleted($this, $actor));
    }

    public function reopen(): void
    {
        if ($this->status === WorkItemStatus::Pending) {
            return;
        }

        $this->update(['status' => WorkItemStatus::Pending]);
    }
}
