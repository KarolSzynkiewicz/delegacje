<?php

namespace App\Models;

use App\Contracts\TaskSubject;
use App\Enums\CommentableType;
use App\Enums\LogisticsEventType;
use App\Enums\WorkItemStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model implements TaskSubject
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::deleting(function (Comment $comment) {
            foreach ($comment->replies()->get() as $reply) {
                $reply->delete();
            }
            $comment->mentions()->get()->each->delete();
            $comment->attachments->each->delete();
            $comment->likes()->delete();
        });
    }

    protected $fillable = [
        'commentable_type',
        'commentable_id',
        'user_id',
        'parent_id',
        'body',
    ];

    protected $casts = [
        'commentable_type' => CommentableType::class,
    ];

    /**
     * Get the parent commentable model.
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Załączniki przypisane do komentarza (np. zdjęcia z placu budowy).
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('created_at');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CommentLike::class);
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(ProjectTask::class, 'subject');
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(CommentMention::class);
    }

    public function approvalRequests(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class);
    }

    public function mentionFor(?int $userId): ?CommentMention
    {
        if (! $userId) {
            return null;
        }

        $match = fn (CommentMention $mention): bool => (int) $mention->assigned_to === $userId
            && $mention->status !== WorkItemStatus::Cancelled;

        if ($this->relationLoaded('mentions')) {
            return $this->mentions->first($match);
        }

        return $this->mentions()
            ->where('assigned_to', $userId)
            ->where('status', '!=', WorkItemStatus::Cancelled)
            ->first();
    }

    public function approvalFor(?int $userId): ?ApprovalRequest
    {
        if (! $userId) {
            return null;
        }

        $match = fn (ApprovalRequest $approval): bool => (int) $approval->approver_id === $userId;

        if ($this->relationLoaded('approvalRequests')) {
            return $this->approvalRequests->first($match);
        }

        return $this->approvalRequests()
            ->where('approver_id', $userId)
            ->first();
    }

    /**
     * Karta źródła (pojazd, projekt, …) — URL bez kotwicy komentarza.
     *
     * @return array{url: string, label: string, icon: string}|null
     */
    public function commentableCard(): ?array
    {
        $url = $this->commentableShowUrl();
        if (! $url) {
            return null;
        }

        return [
            'url' => $url,
            'label' => $this->notificationContextLabel(),
            'icon' => $this->commentableIcon(),
        ];
    }

    public function commentableIcon(): string
    {
        $morph = $this->resolvedCommentable();

        return match (true) {
            $morph instanceof Vehicle => 'bi-car-front',
            $morph instanceof Project => 'bi-folder',
            $morph instanceof ProjectTask => 'bi-check2-square',
            $morph instanceof RecruitmentProcess, $morph instanceof RecruitmentCandidate => 'bi-person-badge',
            $morph instanceof Employee => 'bi-person',
            $morph instanceof Accommodation => 'bi-house',
            $morph instanceof Location => 'bi-geo-alt',
            $morph instanceof LogisticsEvent => 'bi-signpost-split',
            $morph instanceof Sprint => 'bi-kanban',
            default => 'bi-chat-dots',
        };
    }

    public function taskCardUrl(): string
    {
        return $this->urlWithCommentAnchor();
    }

    public function taskCardLabel(): string
    {
        return $this->notificationContextLabel();
    }

    public function taskCardIcon(): string
    {
        return 'bi-chat-dots';
    }

    /**
     * Get the user who wrote the comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * URL do strony z komentarzem (kotwica #comment-{id}).
     */
    public function urlWithCommentAnchor(): string
    {
        $base = $this->commentableShowUrl();

        return $base ? $base.'#comment-'.$this->id : url('/');
    }

    public function commentableShowUrl(): ?string
    {
        $morph = $this->resolvedCommentable();
        if (! $morph) {
            return null;
        }

        return match (true) {
            $morph instanceof ProjectTask => route('tasks.show', $morph),
            $morph instanceof Project => route('projects.show', $morph),
            $morph instanceof Vehicle => route('vehicles.show', $morph),
            $morph instanceof Accommodation => route('accommodations.show', $morph),
            $morph instanceof Location => route('locations.show', $morph),
            $morph instanceof Employee => route('employees.show', $morph),
            $morph instanceof RecruitmentProcess => route('recruitment-processes.show', $morph),
            $morph instanceof RecruitmentCandidate => route('recruitment-processes.index'),
            $morph instanceof Sprint => route('sprints.show', $morph),
            $morph instanceof LogisticsEvent => match ($morph->type) {
                LogisticsEventType::DEPARTURE => route('departures.show', $morph),
                LogisticsEventType::TRANSFER => route('transfers.show', $morph),
                LogisticsEventType::RETURN => route('return-trips.show', $morph),
            },
            default => null,
        };
    }

    public function notificationContextLabel(): string
    {
        $morph = $this->resolvedCommentable();
        if (! $morph) {
            return 'komentarz #'.$this->id;
        }

        return match (true) {
            $morph instanceof ProjectTask => filled($morph->name) ? (string) $morph->name : 'Zadanie #'.$morph->id,
            $morph instanceof Project => filled($morph->name) ? (string) $morph->name : 'Projekt #'.$morph->id,
            $morph instanceof Vehicle => filled($morph->registration_number) ? (string) $morph->registration_number : 'Pojazd #'.$morph->id,
            $morph instanceof Accommodation => filled($morph->name) ? (string) $morph->name : 'Nocleg #'.$morph->id,
            $morph instanceof Location => filled($morph->name) ? (string) $morph->name : 'Lokalizacja #'.$morph->id,
            $morph instanceof Employee => filled($morph->full_name) ? $morph->full_name : 'Pracownik #'.$morph->id,
            $morph instanceof LogisticsEvent => trim($morph->type->label().' '.($morph->event_date?->format('d.m.Y') ?? '')),
            $morph instanceof RecruitmentProcess => filled($morph->full_name) ? 'Rekrutacja: '.$morph->full_name : 'Rekrutacja #'.$morph->id,
            $morph instanceof RecruitmentCandidate => filled($morph->full_name) ? 'Kandydat: '.$morph->full_name : 'Kandydat #'.$morph->id,
            $morph instanceof Sprint => filled($morph->name) ? (string) $morph->name : 'Sprint #'.$morph->id,
            default => class_basename($morph).' #'.$morph->id,
        };
    }

    public function bodyExcerpt(int $max = 140): ?string
    {
        $body = trim(preg_replace('/\s+/u', ' ', (string) ($this->body ?? '')) ?? '');
        if ($body === '') {
            return null;
        }

        if (mb_strlen($body) <= $max) {
            return $body;
        }

        return mb_substr($body, 0, $max - 1).'…';
    }

    public function quoteLabel(): string
    {
        $excerpt = $this->bodyExcerpt(160);
        if ($excerpt) {
            return $excerpt;
        }

        $this->loadMissing('attachments');

        return $this->attachments->isNotEmpty() ? 'załącznik' : '…';
    }

    public function resolvedCommentable(): ?Model
    {
        if ($this->relationLoaded('commentable') && $this->commentable) {
            return $this->commentable;
        }

        $this->loadMissing('commentable');
        if ($this->commentable) {
            return $this->commentable;
        }

        $type = $this->commentable_type instanceof CommentableType
            ? $this->commentable_type
            : CommentableType::tryFrom((string) $this->commentable_type);

        if (! $type || ! $this->commentable_id) {
            return null;
        }

        return $type->modelClass()::query()->find($this->commentable_id);
    }

    /**
     * Scope a query to filter by commentable type.
     */
    public function scopeForModel(Builder $query, CommentableType $type): Builder
    {
        return $query->where('commentable_type', $type->value);
    }
}
