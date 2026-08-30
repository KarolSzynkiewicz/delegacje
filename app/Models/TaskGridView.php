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
        'status_filter',
        'assigned_filters',
        'created_by_filters',
        'type_filter',
        'filter_join',
        'filter_ops',
    ];

    protected $casts = [
        'visible_columns' => 'array',
        'column_widths' => 'array',
        'my_tasks_only' => 'boolean',
        'is_global' => 'boolean',
        'type_filter' => 'array',
        'status_filter' => 'array',
        'assigned_filters' => 'array',
        'created_by_filters' => 'array',
        'filter_ops' => 'array',
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
        if (! empty($this->status_filter) && is_array($this->status_filter)) {
            $params['statuses'] = $this->status_filter;
        }
        if (! empty($this->assigned_filters) && is_array($this->assigned_filters)) {
            $params['assigned'] = $this->assigned_filters;
            if (count($this->assigned_filters) === 1) {
                $params['assignedFilter'] = $this->assigned_filters[0];
            }
        } elseif ($this->assigned_filter !== '' && $this->assigned_filter !== null) {
            $params['assignedFilter'] = $this->assigned_filter;
        } elseif ($this->my_tasks_only) {
            // Kompatybilność wsteczna: widoki zapisane przed dodaniem kolumny assigned_filter.
            $params['assignedFilter'] = 'me';
        }
        if (! empty($this->created_by_filters) && is_array($this->created_by_filters)) {
            $params['createdBy'] = $this->created_by_filters;
            if (count($this->created_by_filters) === 1) {
                $params['createdByFilter'] = $this->created_by_filters[0];
            }
        } elseif ($this->created_by_filter !== '' && $this->created_by_filter !== null) {
            $params['createdByFilter'] = $this->created_by_filter;
        }
        if (! empty($this->type_filter)) {
            $params['types'] = $this->type_filter;
        }
        if (($this->filter_join ?? 'and') === 'or') {
            $params['join'] = 'or';
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
