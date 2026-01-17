<?php

namespace App\Models;

use App\Enums\ProjectType;
use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Project extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'location_id',
        'name',
        'description',
        'status',
        'type',
        'client_name',
        'budget',
        'hourly_rate',
        'contract_amount',
        'currency',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => ProjectStatus::class,
        'type' => ProjectType::class,
        'hourly_rate' => 'decimal:2',
        'contract_amount' => 'decimal:2',
    ];

    /**
     * Get the location that owns the project.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the demands for this project.
     */
    public function demands(): HasMany
    {
        return $this->hasMany(ProjectDemand::class);
    }

    /**
     * Get the assignments for this project.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class);
    }

    /**
     * Get the employees assigned to this project (M:N relationship).
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'project_assignments')
            ->withPivot('role_id', 'start_date', 'end_date', 'status', 'notes')
            ->withTimestamps();
    }

    /**
     * Get active assignments for this project.
     */
    public function activeAssignments(): HasMany
    {
        return $this->assignments()->active();
    }

    /**
     * Get the variable costs for this project.
     */
    public function variableCosts(): HasMany
    {
        return $this->hasMany(ProjectVariableCost::class);
    }

    /**
     * Scope a query to only include active projects.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Check if there is a demand for a specific role in a date range.
     */
    public function hasDemandForRoleInDateRange(int $roleId, string $startDate, string $endDate): bool
    {
        return $this->demands()
            ->where('role_id', $roleId)
            ->overlappingWith($startDate, $endDate)
            ->exists();
    }
}
