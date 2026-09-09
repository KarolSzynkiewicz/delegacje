<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class CommentView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'comment_id',
        'user_id',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Collection<int, int>|iterable<int>  $commentIds
     */
    public static function recordFor(iterable $commentIds, User $user): void
    {
        $ids = collect($commentIds)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return;
        }

        $now = now();
        $rows = $ids->map(fn ($id) => [
            'comment_id' => (int) $id,
            'user_id' => $user->id,
            'viewed_at' => $now,
        ])->all();

        static::query()->upsert($rows, ['comment_id', 'user_id'], ['viewed_at']);
    }
}
