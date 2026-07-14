<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskSubtaskEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'subtask_id',
        'user_id',
        'event',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function subtask(): BelongsTo
    {
        return $this->belongsTo(TaskSubtask::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(TaskSubtask $subtask, string $event, ?int $userId): void
    {
        static::create([
            'subtask_id' => $subtask->id,
            'user_id'    => $userId,
            'event'      => $event,
            'created_at' => now(),
        ]);
    }
}
