<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentGridView extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'status',
        'flag',
        'mine',
        'former_employee',
        'recruiter',
        'referral_source',
        'rejection_filter',
        'advanced_filters',
        'search',
        'sort_field',
        'sort_direction',
    ];

    protected $casts = [
        'mine' => 'boolean',
        'former_employee' => 'boolean',
        'advanced_filters' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string|bool>
     */
    public function queryStringParams(): array
    {
        $params = ['view' => $this->slug];

        if ($this->status !== '') {
            $params['status'] = $this->status;
        }
        if ($this->flag !== '') {
            $params['flag'] = $this->flag;
        }
        if ($this->mine) {
            $params['mine'] = 'true';
        }
        if ($this->former_employee) {
            $params['formerEmployee'] = 'true';
        }
        if ($this->recruiter !== '') {
            $params['recruiter'] = $this->recruiter;
        }
        if ($this->referral_source !== '') {
            $params['referralSource'] = $this->referral_source;
        }
        if ($this->rejection_filter !== '') {
            $params['rejection'] = $this->rejection_filter;
        }
        if ($this->search !== '') {
            $params['q'] = $this->search;
        }
        if (($this->sort_field ?: 'created_at') !== 'created_at') {
            $params['sort'] = $this->sort_field;
        }
        if (($this->sort_direction ?: 'desc') !== 'desc') {
            $params['dir'] = $this->sort_direction;
        }

        return $params;
    }
}
