<?php

namespace App\Models;

use App\Enums\CommentableType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::deleting(function (Comment $comment) {
            $comment->attachments->each->delete();
        });
    }

    protected $fillable = [
        'commentable_type',
        'commentable_id',
        'user_id',
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

    /**
     * Get the user who wrote the comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to filter by commentable type.
     */
    public function scopeForModel(Builder $query, CommentableType $type): Builder
    {
        return $query->where('commentable_type', $type->value);
    }
}
