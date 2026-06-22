<?php

namespace App\Models;

use App\Enums\CommentableType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::deleting(function (Comment $comment) {
            foreach ($comment->replies()->get() as $reply) {
                $reply->delete();
            }
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
        $this->loadMissing('commentable');
        $morph = $this->commentable;
        if (! $morph) {
            return url('/');
        }

        $hash = '#comment-'.$this->id;

        return match (true) {
            $morph instanceof ProjectTask => route('tasks.show', $morph).$hash,
            $morph instanceof Project => route('projects.show', $morph).$hash,
            $morph instanceof Vehicle => route('vehicles.show', $morph).$hash,
            $morph instanceof Accommodation => route('accommodations.show', $morph).$hash,
            $morph instanceof Location => route('locations.show', $morph).$hash,
            $morph instanceof Employee => route('employees.show', $morph).$hash,
            $morph instanceof RecruitmentApplication => route('recruitment-applications.show', $morph).$hash,
            default => url('/'),
        };
    }

    /**
     * Scope a query to filter by commentable type.
     */
    public function scopeForModel(Builder $query, CommentableType $type): Builder
    {
        return $query->where('commentable_type', $type->value);
    }
}
