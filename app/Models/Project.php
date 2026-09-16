<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Traits\HasComments;
use App\Traits\HasDateRange;
use App\Traits\HasEquipmentConsumptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    use HasComments, HasDateRange, HasEquipmentConsumptions, HasFactory;

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
        'start_date',
        'end_date',
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
        'start_date' => 'date',
        'end_date' => 'date',
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
     * Get the files for this project.
     */
    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    /**
     * Get the managers (users) for this project.
     */
    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_managers')
            ->withTimestamps();
    }

    /**
     * Field / site leads (employees) for this project, with date ranges.
     * Distinct from managers() — those are system users with /mine access.
     */
    public function siteLeads(): HasMany
    {
        return $this->hasMany(ProjectSiteLead::class)->orderByDesc('start_date');
    }

    /**
     * The employee currently leading the crew on site (today).
     */
    public function currentSiteLead(): HasOne
    {
        $today = now()->toDateString();

        return $this->hasOne(ProjectSiteLead::class)->ofMany(
            ['start_date' => 'max'],
            function ($query) use ($today) {
                $query->whereDate('start_date', '<=', $today)
                    ->where(function ($inner) use ($today) {
                        $inner->whereNull('end_date')
                            ->orWhereDate('end_date', '>=', $today);
                    });
            }
        );
    }

    /**
     * Scope a query to only include active projects.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Unique, unterminated employees whose assignment overlaps the date range.
     *
     * @return \Illuminate\Support\Collection<int, Employee>
     */
    public function employeesAssignedInDateRange($startDate, $endDate): \Illuminate\Support\Collection
    {
        return $this->assignments()
            ->overlappingWith($startDate, $endDate)
            ->with('employee')
            ->get()
            ->pluck('employee')
            ->filter(fn ($employee) => $employee && $employee->terminated_at === null)
            ->unique('id')
            ->sortBy([
                ['last_name', 'asc'],
                ['first_name', 'asc'],
            ])
            ->values();
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
