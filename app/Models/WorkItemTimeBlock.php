<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkItemTimeBlock extends Model
{
    protected $fillable = [
        'work_item_id',
        'user_id',
        'starts_at',
        'ends_at',
        'all_day',
        'created_by_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'all_day' => 'boolean',
    ];

    public function workItem(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function label(): string
    {
        if ($this->all_day) {
            return $this->starts_at->format('d.m').' · cały dzień';
        }

        return $this->starts_at->format('d.m H:i').'–'.$this->ends_at->format('H:i');
    }
}
