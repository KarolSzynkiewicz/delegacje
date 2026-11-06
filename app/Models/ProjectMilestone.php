<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'sort_order',
        'is_done',
        'done_at',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_done' => 'boolean',
        'done_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function markDone(bool $done = true): void
    {
        $this->forceFill([
            'is_done' => $done,
            'done_at' => $done ? now() : null,
        ])->save();
    }
}
