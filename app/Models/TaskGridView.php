<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskGridView extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'visible_columns',
        'column_widths',
        'group_by',
        'sort_field',
        'sort_direction',
    ];

    protected $casts = [
        'visible_columns' => 'array',
        'column_widths'   => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
