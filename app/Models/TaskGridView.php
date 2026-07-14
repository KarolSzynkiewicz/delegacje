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
        'search_task',
        'search_project',
        'search_category',
        'search_assigned_to',
        'status',
        'my_tasks_only',
    ];

    protected $casts = [
        'visible_columns' => 'array',
        'column_widths'   => 'array',
        'my_tasks_only'   => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Parametry query string do bookmarków / przekierowania z menu.
     *
     * @return array<string, string|bool>
     */
    public function queryStringParams(): array
    {
        $params = ['view' => $this->slug];

        if ($this->search_task !== '') {
            $params['searchTask'] = $this->search_task;
        }
        if ($this->search_project !== '') {
            $params['searchProject'] = $this->search_project;
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
        if ($this->my_tasks_only) {
            $params['myTasksOnly'] = 'true';
        }
        if (($this->group_by ?? '') !== '') {
            $params['groupBy'] = $this->group_by;
        }
        if (($this->sort_field ?: 'created_at') !== 'created_at') {
            $params['sortField'] = $this->sort_field;
        }
        if (($this->sort_direction ?: 'desc') !== 'desc') {
            $params['sortDirection'] = $this->sort_direction;
        }

        return $params;
    }
}
