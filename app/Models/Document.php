<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_periodic',
        'is_required',
        'is_company_scoped',
        'planner_icon',
    ];

    protected $casts = [
        'is_periodic' => 'boolean',
        'is_required' => 'boolean',
        'is_company_scoped' => 'boolean',
    ];

    protected static ?\Illuminate\Support\Collection $requiredTypesCache = null;

    protected static function booted(): void
    {
        static::saved(fn () => static::flushRequiredTypesCache());
        static::deleted(fn () => static::flushRequiredTypesCache());
    }

    public static function flushRequiredTypesCache(): void
    {
        static::$requiredTypesCache = null;
    }

    public static function requiredTypes(): \Illuminate\Support\Collection
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('documents', 'is_required')) {
            return collect();
        }

        return static::$requiredTypesCache ??= static::query()
            ->where('is_required', true)
            ->get();
    }

    /**
     * Get all employee documents of this type.
     */
    public function employeeDocuments(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function label(): string
    {
        return $this->name;
    }

    public function isRequiredForEmployeeDuring(Employee $employee, $startDate, $endDate): bool
    {
        if (! $this->is_required) {
            return false;
        }

        if (! $this->is_company_scoped) {
            return true;
        }

        return $employee->companyIdsAssignedInDateRange($startDate, $endDate)->isNotEmpty();
    }

    public function requirementLabelForCompany(?Company $company): string
    {
        if ($company) {
            return $this->name.' · '.$company->name;
        }

        return $this->name;
    }

    public function showsInPlanner(): bool
    {
        return filled($this->planner_icon);
    }

    public function plannerIconClass(): ?string
    {
        return $this->showsInPlanner() ? $this->planner_icon : null;
    }
}
