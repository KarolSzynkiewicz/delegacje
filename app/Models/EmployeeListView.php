<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeListView extends Model
{
    protected $fillable = [
        'user_id',
        'is_global',
        'name',
        'slug',
        'visible_columns',
        'sort_field',
        'sort_direction',
        'filters',
    ];

    protected $casts = [
        'is_global' => 'boolean',
        'visible_columns' => 'array',
        'filters' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeVisibleTo(Builder $query, ?User $user): void
    {
        if (! $user) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $inner) use ($user) {
            $inner->where('user_id', $user->id)->orWhere('is_global', true);
        });
    }

    public function canBeManagedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ((int) $this->user_id === (int) $user->id) {
            return true;
        }

        return $this->is_global && $user->isAdmin();
    }
}
