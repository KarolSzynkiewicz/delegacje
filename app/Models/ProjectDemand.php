<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasDateRange;

class ProjectDemand extends Model
{
    use HasFactory, HasDateRange;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'role_id',
        'required_count',
        'date_from',
        'date_to',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_from' => 'datetime',
        'date_to' => 'datetime',
        'required_count' => 'integer',
    ];

    /**
     * Override column names for HasDateRange trait.
     */
    public function getStartDateColumn(): string
    {
        return 'date_from';
    }

    /**
     * Override column names for HasDateRange trait.
     */
    public function getEndDateColumn(): string
    {
        return 'date_to';
    }

    /**
     * Get the project that owns the demand.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the role required for this demand.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
