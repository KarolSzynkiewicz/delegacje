<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskGridView extends Model
{
    protected $fillable = [
        'user_id',
        'is_global',
        'name',
        'slug',
        'visible_columns',
        'column_widths',
        'group_by',
        'sort_field',
        'sort_direction',
        'search_task',
        'search_project',
        'search_category',
        'search_assigned_to',
        'status',
        'my_tasks_only',
        'assigned_filter',
        'created_by_filter',
        'type_filter',
    ];

    protected $casts = [
        'visible_columns' => 'array',
        'column_widths' => 'array',
        'my_tasks_only' => 'boolean',
        'is_global' => 'boolean',
        'type_filter' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Widoki własne użytkownika oraz globalne (dla wszystkich).
     *
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

    public static function findVisibleTo(User $user, string $slug): ?self
    {
        return static::query()
            ->visibleTo($user)
            ->where('slug', $slug)
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$user->id])
            ->first();
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

    /**
     * Parametry query string do bookmarków / przekierowania z menu.
     *
     * @return array<string, string|bool|list<string>>
     */
    public function queryStringParams(): array
    {
        $params = ['view' => $this->slug];

        if ($this->search_task !== '') {
            $params['searchTask'] = $this->search_task;
        }
        if ($this->search_category !== '') {
            $params['searchCategory'] = $this->search_category;
        }
        if ($this->search_assigned_to !== '') {
            $params['searchAssignedTo'] = $this->search_assigned_to;
        }
        if ($this->status !== '') {
            $params['status'] = $this->status;
        }
        if ($this->assigned_filter !== '' && $this->assigned_filter !== null) {
            $params['assignedFilter'] = $this->assigned_filter;
        } elseif ($this->my_tasks_only) {
            // Kompatybilność wsteczna: widoki zapisane przed dodaniem kolumny assigned_filter.
            $params['assignedFilter'] = 'me';
        }
        if ($this->created_by_filter !== '' && $this->created_by_filter !== null) {
            $params['createdByFilter'] = $this->created_by_filter;
        }
        if (! empty($this->type_filter)) {
            $params['types'] = $this->type_filter;
        }
        if (($this->group_by ?? '') !== '' && $this->group_by !== 'project') {
            $params['groupBy'] = $this->group_by;
        }
        if (($this->sort_field ?: 'created_at') !== 'created_at' && $this->sort_field !== 'project') {
            $params['sortField'] = $this->sort_field;
        }
        if (($this->sort_direction ?: 'desc') !== 'desc') {
            $params['sortDirection'] = $this->sort_direction;
        }

        return $params;
    }
}
