<?php

namespace App\Models;

use App\Enums\ApprovalDecision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class ApprovalRequest extends Model
{
    protected $fillable = [
        'name',
        'description',
        'approver_id',
        'created_by',
        'comment_id',
        'sprint_id',
        'category',
        'priority',
        'due_at',
        'decision',
        'decided_at',
        'decided_by',
    ];

    protected $casts = [
        'decision' => ApprovalDecision::class,
        'due_at' => 'date',
        'decided_at' => 'datetime',
        'priority' => 'integer',
    ];

    protected static function booted(): void
    {
        static::deleting(function (ApprovalRequest $approval) {
            $approval->attachments->each->delete();
        });
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function workItem(): MorphOne
    {
        return $this->morphOne(WorkItem::class, 'source');
    }

    public function isDecided(): bool
    {
        return $this->decision !== null;
    }

    public function isApprover(?User $user): bool
    {
        return $user !== null && (int) $user->id === (int) $this->approver_id;
    }

    public function isCreator(?User $user): bool
    {
        return $user !== null && $this->created_by !== null && (int) $user->id === (int) $this->created_by;
    }

    public function decide(ApprovalDecision $decision, User $actor): void
    {
        if ($this->isDecided()) {
            return;
        }

        $this->update([
            'decision' => $decision,
            'decided_at' => now(),
            'decided_by' => $actor->id,
        ]);
    }
}
