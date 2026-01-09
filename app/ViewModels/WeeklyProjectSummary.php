<?php

namespace App\ViewModels;

use Illuminate\Support\Collection;

class WeeklyProjectSummary
{
    public function __construct(
        protected array $weekData
    ) {}

    public function allHaveVehicle(): bool
    {
        if (!$this->hasAssignedEmployees()) {
            return false;
        }

        return $this->getEmployeesWithoutVehicle()->isEmpty();
    }

    public function allHaveAccommodation(): bool
    {
        if (!$this->hasAssignedEmployees()) {
            return false;
        }

        return $this->getEmployeesWithoutAccommodation()->isEmpty();
    }

    public function getEmployeesWithoutVehicle(): Collection
    {
        if (!$this->hasAssignedEmployees()) {
            return collect();
        }

        return $this->weekData['assigned_employees']->filter(function($employeeData) {
            return empty($employeeData['vehicle']);
        });
    }

    public function getEmployeesWithoutAccommodation(): Collection
    {
        if (!$this->hasAssignedEmployees()) {
            return collect();
        }

        return $this->weekData['assigned_employees']->filter(function($employeeData) {
            return empty($employeeData['accommodation']);
        });
    }

    public function getMissingRoles(): array
    {
        if (empty($this->weekData['requirements_summary']['role_details'])) {
            return [];
        }

        return array_filter($this->weekData['requirements_summary']['role_details'], function($roleDetail) {
            return isset($roleDetail['missing']) && $roleDetail['missing'] > 0;
        });
    }

    public function getExcessRoles(): array
    {
        if (empty($this->weekData['requirements_summary']['role_details'])) {
            return [];
        }

        return array_filter($this->weekData['requirements_summary']['role_details'], function($roleDetail) {
            return isset($roleDetail['excess']) && $roleDetail['excess'] > 0;
        });
    }

    public function getTotalNeeded(): int
    {
        return $this->weekData['requirements_summary']['total_needed'] ?? 0;
    }

    public function getTotalAssigned(): int
    {
        return $this->weekData['requirements_summary']['total_assigned'] ?? 0;
    }

    public function getTotalMissing(): int
    {
        return $this->weekData['requirements_summary']['total_missing'] ?? 0;
    }

    public function getTotalExcess(): int
    {
        return $this->weekData['requirements_summary']['total_excess'] ?? 0;
    }

    public function getProgressPercentage(): int
    {
        $totalNeeded = $this->getTotalNeeded();
        
        if ($totalNeeded <= 0) {
            return 0;
        }

        return round(($this->getTotalAssigned() / $totalNeeded) * 100);
    }

    public function getProgressClass(): string
    {
        $percentage = $this->getProgressPercentage();
        
        if ($percentage == 100) {
            return 'bg-success';
        } elseif ($percentage >= 70) {
            return 'bg-warning';
        }
        
        return 'bg-danger';
    }

    public function getTextClass(): string
    {
        $percentage = $this->getProgressPercentage();
        
        if ($percentage == 100) {
            return 'text-success';
        } elseif ($percentage >= 70) {
            return 'text-warning';
        }
        
        return 'text-danger';
    }

    public function hasIssues(): bool
    {
        return $this->getTotalMissing() > 0 || $this->getTotalExcess() > 0;
    }

    public function hasData(): bool
    {
        return $this->weekData['has_data'] ?? false;
    }

    public function hasAssignedEmployees(): bool
    {
        return isset($this->weekData['assigned_employees']) 
            && $this->weekData['assigned_employees']->isNotEmpty();
    }

    public function getWeekData(): array
    {
        return $this->weekData;
    }
}
