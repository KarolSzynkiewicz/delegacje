<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDemandRole extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_demand_id',
        'role_id',
        'required_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'required_count' => 'integer',
    ];

    /**
     * Get the project demand that owns this role requirement.
     */
    public function projectDemand(): BelongsTo
    {
        return $this->belongsTo(ProjectDemand::class);
    }

    /**
     * Get the role associated with this requirement.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
