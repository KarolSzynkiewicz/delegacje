<?php

namespace App\Services;

use App\Models\Project;
use App\Models\TimeLog;
use App\Models\ProjectAssignment;
use App\Models\EmployeeRate;
use App\Models\Rotation;
use App\Models\ProjectVariableCost;
use App\Models\FixedCost;
use App\Models\Payroll;
use App\Enums\ProjectType;
use App\Enums\PayrollStatus;
use Carbon\Carbon;

class ProfitabilityService
{
    /**
     * Get profitability data for all active projects.
     */
    public function getActiveProjectsProfitability(): array
    {
        $projects = Project::with(['assignments.employee', 'assignments.timeLogs', 'variableCosts'])
            ->where('status', 'active')
            ->get();

        return $projects->map(function ($project) {
            return $this->getProjectProfitability($project);
        })->toArray();
    }

    /**
     * Get profitability data for all active projects for a specific month.
     */
    public function getActiveProjectsProfitabilityForMonth(Carbon $monthStart, Carbon $monthEnd): array
    {
        $projects = Project::with(['assignments.employee', 'assignments.timeLogs', 'variableCosts'])
            ->where('status', 'active')
            ->get();

        return $projects->map(function ($project) use ($monthStart, $monthEnd) {
            return $this->getProjectProfitabilityForMonth($project, $monthStart, $monthEnd);
        })->toArray();
    }

    /**
     * Get profitability data for a single project.
     */
    public function getProjectProfitability(Project $project): array
    {
        $assignments = $project->assignments()->with(['employee', 'timeLogs'])->get();
        $variableCosts = $project->variableCosts;
        
        // Calculate labor costs (from payroll/time logs)
        $laborCosts = $this->calculateLaborCosts($project, $assignments);
        
        // Calculate variable costs
        $variableCostsTotal = $this->calculateVariableCosts($variableCosts);
        
        // Calculate revenue based on project type
        $revenue = $this->calculateRevenue($project, $assignments);
        
        // Calculate margin
        $totalCosts = $laborCosts + $variableCostsTotal;
        $margin = $revenue - $totalCosts;
        $marginPercentage = $revenue > 0 ? ($margin / $revenue) * 100 : 0;
        
        // Get employee count (max from assignments)
        $employeeCount = $assignments->count();
        
        // Calculate estimated hours (8h/day on working days)
        $estimatedHours = $this->calculateEstimatedHours($assignments);
        
        // Calculate actual hours from time logs
        $actualHours = $this->calculateActualHours($assignments);
        
        // Calculate plan execution (actual vs estimated)
        $planExecution = $estimatedHours > 0 ? ($actualHours / $estimatedHours) * 100 : 0;
        
        return [
            'project' => $project,
            'revenue' => round($revenue, 2),
            'labor_costs' => round($laborCosts, 2),
            'variable_costs' => round($variableCostsTotal, 2),
            'total_costs' => round($totalCosts, 2),
            'margin' => round($margin, 2),
            'margin_percentage' => round($marginPercentage, 2),
            'employee_count' => $employeeCount,
            'estimated_hours' => round($estimatedHours, 2),
            'actual_hours' => round($actualHours, 2),
            'plan_execution' => round($planExecution, 2),
            'currency' => $project->currency ?? 'PLN',
        ];
    }

    /**
     * Get profitability data for a single project for a specific month.
     */
    public function getProjectProfitabilityForMonth(Project $project, Carbon $monthStart, Carbon $monthEnd): array
    {
        // Get assignments that overlap with the month
        $assignments = $project->assignments()
            ->with(['employee', 'timeLogs' => function ($query) use ($monthStart, $monthEnd) {
                $query->whereBetween('start_time', [
                    $monthStart->copy()->startOfDay(),
                    $monthEnd->copy()->endOfDay()
                ]);
            }])
            ->where(function ($query) use ($monthStart, $monthEnd) {
                $query->where(function ($q) use ($monthStart, $monthEnd) {
                    $q->where('start_date', '<=', $monthEnd)
                      ->where(function ($q2) use ($monthStart) {
                          $q2->whereNull('end_date')
                             ->orWhere('end_date', '>=', $monthStart);
                      });
                });
            })
            ->get();
        
        // Get variable costs for the month
        $variableCosts = $project->variableCosts()
            ->where('created_at', '>=', $monthStart->startOfDay())
            ->where('created_at', '<=', $monthEnd->endOfDay())
            ->get();
        
        // Calculate labor costs (from payroll/time logs) for the month - grouped by currency
        $laborCostsByCurrency = $this->calculateLaborCostsForMonthByCurrency($project, $assignments, $monthStart, $monthEnd);
        
        // Calculate paid and unpaid labor costs by currency
        $paidLaborCostsByCurrency = $this->calculatePaidLaborCostsForMonthByCurrency($project, $assignments, $monthStart, $monthEnd);
        
        // Calculate variable costs for the month - grouped by currency
        $variableCostsByCurrency = $this->calculateVariableCostsByCurrency($variableCosts);
        
        // Calculate revenue based on project type for the month
        $revenue = $this->calculateRevenueForMonth($project, $assignments, $monthStart, $monthEnd);
        $revenueCurrency = $project->currency ?? 'EUR';
        
        // Get employee count (max from assignments in the month)
        $employeeCount = $assignments->count();
        
        // Calculate estimated hours (8h/day on working days) for the month
        $estimatedHours = $this->calculateEstimatedHoursForMonth($assignments, $monthStart, $monthEnd);
        
        // Calculate actual hours from time logs for the month
        $actualHours = $this->calculateActualHoursForMonth($assignments, $monthStart, $monthEnd);
        
        // Calculate plan execution (actual vs estimated)
        $planExecution = $estimatedHours > 0 ? ($actualHours / $estimatedHours) * 100 : 0;
        
        return [
            'project' => $project,
            'revenue' => round($revenue, 2),
            'revenue_currency' => $revenueCurrency,
            'labor_costs_by_currency' => $laborCostsByCurrency,
            'paid_labor_costs_by_currency' => $paidLaborCostsByCurrency,
            'variable_costs_by_currency' => $variableCostsByCurrency,
            'employee_count' => $employeeCount,
            'estimated_hours' => round($estimatedHours, 2),
            'actual_hours' => round($actualHours, 2),
            'plan_execution' => round($planExecution, 2),
        ];
    }

    /**
     * Calculate labor costs for a project based on time logs and employee rates.
     */
    protected function calculateLaborCosts(Project $project, $assignments): float
    {
        $totalCost = 0;
        $projectCurrency = $project->currency ?? 'PLN';

        foreach ($assignments as $assignment) {
            $timeLogs = $assignment->timeLogs;
            
            foreach ($timeLogs as $timeLog) {
                $workDate = Carbon::parse($timeLog->start_time)->toDateString();
                $hoursWorked = (float) $timeLog->hours_worked;
                
                if ($hoursWorked <= 0) {
                    continue;
                }
                
                // Find employee rate for this date
                $rate = EmployeeRate::where('employee_id', $assignment->employee_id)
                    ->where('status', 'active')
                    ->where('start_date', '<=', $workDate)
                    ->where(function ($query) use ($workDate) {
                        $query->whereNull('end_date')
                            ->orWhere('end_date', '>=', $workDate);
                    })
                    ->where('currency', $projectCurrency)
                    ->orderBy('start_date', 'desc')
                    ->first();
                
                // If no rate in project currency, try any currency
                if (!$rate) {
                    $rate = EmployeeRate::where('employee_id', $assignment->employee_id)
                        ->where('status', 'active')
                        ->where('start_date', '<=', $workDate)
                        ->where(function ($query) use ($workDate) {
                            $query->whereNull('end_date')
                                ->orWhere('end_date', '>=', $workDate);
                        })
                        ->orderBy('start_date', 'desc')
                        ->first();
                }
                
                if ($rate) {
                    // TODO: Currency conversion if needed
                    $cost = $hoursWorked * (float) $rate->amount;
                    $totalCost += $cost;
                }
            }
        }

        return $totalCost;
    }

    /**
     * Calculate labor costs for a project for a specific month, grouped by currency.
     */
    protected function calculateLaborCostsForMonthByCurrency(Project $project, $assignments, Carbon $monthStart, Carbon $monthEnd): array
    {
        $costsByCurrency = [];

        foreach ($assignments as $assignment) {
            // Get time logs only for the month
            $timeLogs = $assignment->timeLogs->filter(function ($timeLog) use ($monthStart, $monthEnd) {
                $logDate = Carbon::parse($timeLog->start_time);
                return $logDate->gte($monthStart->startOfDay()) && $logDate->lte($monthEnd->endOfDay());
            });
            
            foreach ($timeLogs as $timeLog) {
                $workDate = Carbon::parse($timeLog->start_time)->toDateString();
                $hoursWorked = (float) $timeLog->hours_worked;
                
                if ($hoursWorked <= 0) {
                    continue;
                }
                
                // Find employee rate for this date (any currency)
                $rate = EmployeeRate::where('employee_id', $assignment->employee_id)
                    ->where('status', 'active')
                    ->where('start_date', '<=', $workDate)
                    ->where(function ($query) use ($workDate) {
                        $query->whereNull('end_date')
                            ->orWhere('end_date', '>=', $workDate);
                    })
                    ->orderBy('start_date', 'desc')
                    ->first();
                
                if ($rate) {
                    $currency = $rate->currency;
                    $cost = $hoursWorked * (float) $rate->amount;
                    
                    if (!isset($costsByCurrency[$currency])) {
                        $costsByCurrency[$currency] = 0;
                    }
                    $costsByCurrency[$currency] += $cost;
                }
            }
        }

        // Round all values
        foreach ($costsByCurrency as $currency => $cost) {
            $costsByCurrency[$currency] = round($cost, 2);
        }

        return $costsByCurrency;
    }

    /**
     * Calculate paid labor costs for a project for a specific month, grouped by currency.
     * Only includes costs from payrolls with status "paid".
     */
    protected function calculatePaidLaborCostsForMonthByCurrency(Project $project, $assignments, Carbon $monthStart, Carbon $monthEnd): array
    {
        $paidCostsByCurrency = [];

        // Get all employees assigned to this project
        $employeeIds = $assignments->pluck('employee_id')->unique();

        // Get all paid payrolls for these employees that overlap with the month
        $paidPayrolls = Payroll::whereIn('employee_id', $employeeIds)
            ->where('status', PayrollStatus::PAID)
            ->where(function ($query) use ($monthStart, $monthEnd) {
                $query->where(function ($q) use ($monthStart, $monthEnd) {
                    $q->where('period_start', '<=', $monthEnd)
                      ->where('period_end', '>=', $monthStart);
                });
            })
            ->get();

        // For each paid payroll, calculate the proportion of costs that belong to this project
        foreach ($paidPayrolls as $payroll) {
            $payrollStart = Carbon::parse($payroll->period_start);
            $payrollEnd = Carbon::parse($payroll->period_end);
            $payrollCurrency = $payroll->currency;
            
            // Get time logs for this employee in the payroll period that belong to this project
            $timeLogs = TimeLog::whereHas('projectAssignment', function ($query) use ($project, $payroll) {
                $query->where('project_id', $project->id)
                      ->where('employee_id', $payroll->employee_id);
            })
            ->whereBetween('start_time', [
                $payrollStart->copy()->startOfDay(),
                $payrollEnd->copy()->endOfDay()
            ])
            ->whereBetween('start_time', [
                $monthStart->copy()->startOfDay(),
                $monthEnd->copy()->endOfDay()
            ])
            ->get();

            // Calculate total hours for this project in the payroll period
            $projectHours = $timeLogs->sum('hours_worked');
            
            // Get all time logs for this employee in the payroll period (all projects)
            $allTimeLogs = TimeLog::whereHas('projectAssignment', function ($query) use ($payroll) {
                $query->where('employee_id', $payroll->employee_id);
            })
            ->whereBetween('start_time', [
                $payrollStart->copy()->startOfDay(),
                $payrollEnd->copy()->endOfDay()
            ])
            ->get();

            $allHours = $allTimeLogs->sum('hours_worked');
            
            // Calculate proportion of payroll that belongs to this project
            if ($allHours > 0 && $projectHours > 0) {
                $proportion = $projectHours / $allHours;
                // Use hours_amount from payroll (which is the cost for hours)
                $projectCost = (float) $payroll->hours_amount * $proportion;
                
                if (!isset($paidCostsByCurrency[$payrollCurrency])) {
                    $paidCostsByCurrency[$payrollCurrency] = 0;
                }
                $paidCostsByCurrency[$payrollCurrency] += $projectCost;
            }
        }

        // Round all values
        foreach ($paidCostsByCurrency as $currency => $cost) {
            $paidCostsByCurrency[$currency] = round($cost, 2);
        }

        return $paidCostsByCurrency;
    }

    /**
     * Calculate total variable costs for a project.
     */
    protected function calculateVariableCosts($variableCosts): float
    {
        return $variableCosts->sum('amount');
    }

    /**
     * Calculate variable costs grouped by currency.
     */
    protected function calculateVariableCostsByCurrency($variableCosts): array
    {
        $costsByCurrency = [];
        
        foreach ($variableCosts as $cost) {
            $currency = $cost->currency ?? 'PLN';
            if (!isset($costsByCurrency[$currency])) {
                $costsByCurrency[$currency] = 0;
            }
            $costsByCurrency[$currency] += (float) $cost->amount;
        }
        
        // Round all values
        foreach ($costsByCurrency as $currency => $cost) {
            $costsByCurrency[$currency] = round($cost, 2);
        }
        
        return $costsByCurrency;
    }

    /**
     * Calculate revenue based on project type.
     */
    protected function calculateRevenue(Project $project, $assignments): float
    {
        if ($project->type === ProjectType::CONTRACT) {
            // Contract projects: fixed amount
            return (float) ($project->contract_amount ?? 0);
        } else {
            // Hourly projects: hourly_rate * actual_hours
            $actualHours = $this->calculateActualHours($assignments);
            $hourlyRate = (float) ($project->hourly_rate ?? 0);
            return $actualHours * $hourlyRate;
        }
    }

    /**
     * Calculate revenue based on project type for a specific month.
     */
    protected function calculateRevenueForMonth(Project $project, $assignments, Carbon $monthStart, Carbon $monthEnd): float
    {
        if ($project->type === ProjectType::CONTRACT) {
            // Contract projects: calculate proportional amount for the month
            // If project started/ended in this month, calculate proportion
            $projectStart = $project->created_at ? Carbon::parse($project->created_at) : $monthStart;
            $projectEnd = null; // Projects don't have end_date currently
            
            // For contract projects, we calculate monthly proportion
            // If project spans the entire month, use full amount
            // Otherwise calculate proportion
            $daysInMonth = $monthStart->diffInDays($monthEnd) + 1;
            $projectDaysInMonth = 0;
            
            // Calculate how many days of the project fall in this month
            $periodStart = $projectStart->gt($monthStart) ? $projectStart : $monthStart;
            $periodEnd = $projectEnd && $projectEnd->lt($monthEnd) ? $projectEnd : $monthEnd;
            
            if ($periodStart->lte($periodEnd)) {
                $projectDaysInMonth = $periodStart->diffInDays($periodEnd) + 1;
            }
            
            if ($projectDaysInMonth > 0 && $daysInMonth > 0) {
                $proportion = $projectDaysInMonth / $daysInMonth;
                return (float) ($project->contract_amount ?? 0) * $proportion;
            }
            
            return (float) ($project->contract_amount ?? 0);
        } else {
            // Hourly projects: hourly_rate * actual_hours in the month
            $actualHours = $this->calculateActualHoursForMonth($assignments, $monthStart, $monthEnd);
            $hourlyRate = (float) ($project->hourly_rate ?? 0);
            return $actualHours * $hourlyRate;
        }
    }

    /**
     * Calculate estimated hours (8h/day on working days).
     */
    protected function calculateEstimatedHours($assignments): float
    {
        $totalHours = 0;
        $today = Carbon::today();

        foreach ($assignments as $assignment) {
            $startDate = Carbon::parse($assignment->start_date);
            $endDate = $assignment->end_date ? Carbon::parse($assignment->end_date) : $today;
            
            // Count working days (Monday-Friday)
            $workingDays = 0;
            $currentDate = $startDate->copy();
            
            while ($currentDate->lte($endDate)) {
                if ($currentDate->isWeekday()) {
                    $workingDays++;
                }
                $currentDate->addDay();
            }
            
            $totalHours += $workingDays * 8;
        }

        return $totalHours;
    }

    /**
     * Calculate actual hours from time logs.
     */
    protected function calculateActualHours($assignments): float
    {
        $totalHours = 0;

        foreach ($assignments as $assignment) {
            $totalHours += $assignment->timeLogs->sum('hours_worked');
        }

        return (float) $totalHours;
    }

    /**
     * Calculate actual hours from time logs for a specific month.
     */
    protected function calculateActualHoursForMonth($assignments, Carbon $monthStart, Carbon $monthEnd): float
    {
        $totalHours = 0;

        foreach ($assignments as $assignment) {
            $monthTimeLogs = $assignment->timeLogs->filter(function ($timeLog) use ($monthStart, $monthEnd) {
                $logDate = Carbon::parse($timeLog->start_time);
                return $logDate->gte($monthStart->startOfDay()) && $logDate->lte($monthEnd->endOfDay());
            });
            
            $totalHours += $monthTimeLogs->sum('hours_worked');
        }

        return (float) $totalHours;
    }

    /**
     * Calculate estimated hours (8h/day on working days) for a specific month.
     */
    protected function calculateEstimatedHoursForMonth($assignments, Carbon $monthStart, Carbon $monthEnd): float
    {
        $totalHours = 0;

        foreach ($assignments as $assignment) {
            $assignmentStart = Carbon::parse($assignment->start_date);
            $assignmentEnd = $assignment->end_date ? Carbon::parse($assignment->end_date) : $monthEnd;
            
            // Calculate overlap period
            $periodStart = $assignmentStart->gt($monthStart) ? $assignmentStart : $monthStart;
            $periodEnd = $assignmentEnd->lt($monthEnd) ? $assignmentEnd : $monthEnd;
            
            if ($periodStart->gt($periodEnd)) {
                continue; // No overlap
            }
            
            // Count working days (Monday-Friday) in the overlap period
            $workingDays = 0;
            $currentDate = $periodStart->copy();
            
            while ($currentDate->lte($periodEnd)) {
                if ($currentDate->isWeekday()) {
                    $workingDays++;
                }
                $currentDate->addDay();
            }
            
            $totalHours += $workingDays * 8;
        }

        return $totalHours;
    }

    /**
     * Get top employees by revenue (rate * hours).
     */
    public function getTopEmployeesByRevenue(int $limit = 10): array
    {
        $employees = [];
        
        $timeLogs = TimeLog::with(['projectAssignment.employee', 'projectAssignment.project'])
            ->whereHas('projectAssignment.project', function ($query) {
                $query->where('status', 'active');
            })
            ->get();
        
        foreach ($timeLogs as $timeLog) {
            $employee = $timeLog->projectAssignment->employee;
            $project = $timeLog->projectAssignment->project;
            $hoursWorked = (float) $timeLog->hours_worked;
            
            if ($hoursWorked <= 0) {
                continue;
            }
            
            $workDate = Carbon::parse($timeLog->start_time)->toDateString();
            
            // Get employee rate
            $rate = EmployeeRate::where('employee_id', $employee->id)
                ->where('status', 'active')
                ->where('start_date', '<=', $workDate)
                ->where(function ($query) use ($workDate) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', $workDate);
                })
                ->orderBy('start_date', 'desc')
                ->first();
            
            if (!$rate) {
                continue;
            }
            
            $revenue = $hoursWorked * (float) $rate->amount;
            
            if (!isset($employees[$employee->id])) {
                $employees[$employee->id] = [
                    'employee' => $employee,
                    'total_revenue' => 0,
                    'total_hours' => 0,
                ];
            }
            
            $employees[$employee->id]['total_revenue'] += $revenue;
            $employees[$employee->id]['total_hours'] += $hoursWorked;
        }
        
        // Sort by revenue and take top N
        usort($employees, function ($a, $b) {
            return $b['total_revenue'] <=> $a['total_revenue'];
        });
        
        return array_slice($employees, 0, $limit);
    }

    /**
     * Get top employees by revenue for a specific month.
     */
    public function getTopEmployeesByRevenueForMonth(Carbon $monthStart, Carbon $monthEnd, int $limit = 10): array
    {
        $employees = [];
        
        $timeLogs = TimeLog::with(['projectAssignment.employee', 'projectAssignment.project'])
            ->whereHas('projectAssignment.project', function ($query) {
                $query->where('status', 'active');
            })
            ->whereBetween('start_time', [
                $monthStart->copy()->startOfDay(),
                $monthEnd->copy()->endOfDay()
            ])
            ->get();
        
        foreach ($timeLogs as $timeLog) {
            $employee = $timeLog->projectAssignment->employee;
            $hoursWorked = (float) $timeLog->hours_worked;
            
            if ($hoursWorked <= 0) {
                continue;
            }
            
            $workDate = Carbon::parse($timeLog->start_time)->toDateString();
            
            // Get employee rate
            $rate = EmployeeRate::where('employee_id', $employee->id)
                ->where('status', 'active')
                ->where('start_date', '<=', $workDate)
                ->where(function ($query) use ($workDate) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', $workDate);
                })
                ->orderBy('start_date', 'desc')
                ->first();
            
            if (!$rate) {
                continue;
            }
            
            $revenue = $hoursWorked * (float) $rate->amount;
            $currency = $rate->currency;
            
            if (!isset($employees[$employee->id])) {
                $employees[$employee->id] = [
                    'employee' => $employee,
                    'total_revenue_by_currency' => [],
                    'total_hours' => 0,
                ];
            }
            
            if (!isset($employees[$employee->id]['total_revenue_by_currency'][$currency])) {
                $employees[$employee->id]['total_revenue_by_currency'][$currency] = 0;
            }
            $employees[$employee->id]['total_revenue_by_currency'][$currency] += $revenue;
            $employees[$employee->id]['total_hours'] += $hoursWorked;
        }
        
        // Calculate total revenue for sorting (sum all currencies)
        foreach ($employees as $employeeId => $employeeData) {
            $employees[$employeeId]['total_revenue'] = array_sum($employeeData['total_revenue_by_currency']);
            // Round revenue by currency
            foreach ($employees[$employeeId]['total_revenue_by_currency'] as $currency => $amount) {
                $employees[$employeeId]['total_revenue_by_currency'][$currency] = round($amount, 2);
            }
        }
        
        // Sort by total revenue and take top N
        usort($employees, function ($a, $b) {
            return $b['total_revenue'] <=> $a['total_revenue'];
        });
        
        return array_slice($employees, 0, $limit);
    }

    /**
     * Get employees with longest rotations.
     */
    public function getEmployeesWithLongestRotations(int $limit = 10): array
    {
        $rotations = Rotation::with('employee')
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->get();
        
        $employees = [];
        
        foreach ($rotations as $rotation) {
            $startDate = Carbon::parse($rotation->start_date);
            $endDate = Carbon::parse($rotation->end_date);
            $duration = $startDate->diffInDays($endDate);
            
            $employeeId = $rotation->employee_id;
            
            if (!isset($employees[$employeeId])) {
                $employees[$employeeId] = [
                    'employee' => $rotation->employee,
                    'total_days' => 0,
                    'rotation_count' => 0,
                ];
            }
            
            $employees[$employeeId]['total_days'] += $duration;
            $employees[$employeeId]['rotation_count']++;
        }
        
        // Sort by total days
        usort($employees, function ($a, $b) {
            return $b['total_days'] <=> $a['total_days'];
        });
        
        return array_slice($employees, 0, $limit);
    }

    /**
     * Get summary: revenue vs costs for all active projects.
     */
    public function getRevenueVsCostsSummary(): array
    {
        $projects = Project::with(['assignments.timeLogs', 'variableCosts'])
            ->where('status', 'active')
            ->get();
        
        $totalRevenue = 0;
        $totalLaborCosts = 0;
        $totalVariableCosts = 0;
        
        foreach ($projects as $project) {
            $assignments = $project->assignments;
            $revenue = $this->calculateRevenue($project, $assignments);
            $laborCosts = $this->calculateLaborCosts($project, $assignments);
            $variableCosts = $this->calculateVariableCosts($project->variableCosts);
            
            $totalRevenue += $revenue;
            $totalLaborCosts += $laborCosts;
            $totalVariableCosts += $variableCosts;
        }
        
        $totalCosts = $totalLaborCosts + $totalVariableCosts;
        $totalMargin = $totalRevenue - $totalCosts;
        $marginPercentage = $totalRevenue > 0 ? ($totalMargin / $totalRevenue) * 100 : 0;
        
        return [
            'total_revenue' => round($totalRevenue, 2),
            'total_labor_costs' => round($totalLaborCosts, 2),
            'total_variable_costs' => round($totalVariableCosts, 2),
            'total_costs' => round($totalCosts, 2),
            'total_margin' => round($totalMargin, 2),
            'margin_percentage' => round($marginPercentage, 2),
        ];
    }

    /**
     * Get summary: revenue vs costs for all active projects for a specific month.
     */
    public function getRevenueVsCostsSummaryForMonth(Carbon $monthStart, Carbon $monthEnd): array
    {
        $projects = Project::with(['assignments.timeLogs', 'variableCosts'])
            ->where('status', 'active')
            ->get();
        
        $revenueByCurrency = [];
        $laborCostsByCurrency = [];
        $variableCostsByCurrency = [];
        
        foreach ($projects as $project) {
            // Get assignments that overlap with the month
            $assignments = $project->assignments()
                ->with(['timeLogs' => function ($query) use ($monthStart, $monthEnd) {
                    $query->whereBetween('start_time', [
                        $monthStart->copy()->startOfDay(),
                        $monthEnd->copy()->endOfDay()
                    ]);
                }])
                ->where(function ($query) use ($monthStart, $monthEnd) {
                    $query->where(function ($q) use ($monthStart, $monthEnd) {
                        $q->where('start_date', '<=', $monthEnd)
                          ->where(function ($q2) use ($monthStart) {
                              $q2->whereNull('end_date')
                                 ->orWhere('end_date', '>=', $monthStart);
                          });
                    });
                })
                ->get();
            
            // Get variable costs for the month
            $variableCosts = $project->variableCosts()
                ->where('created_at', '>=', $monthStart->startOfDay())
                ->where('created_at', '<=', $monthEnd->endOfDay())
                ->get();
            
            // Calculate revenue by currency
            $revenue = $this->calculateRevenueForMonth($project, $assignments, $monthStart, $monthEnd);
            $revenueCurrency = $project->currency ?? 'EUR';
            if (!isset($revenueByCurrency[$revenueCurrency])) {
                $revenueByCurrency[$revenueCurrency] = 0;
            }
            $revenueByCurrency[$revenueCurrency] += $revenue;
            
            // Calculate labor costs by currency
            $projectLaborCostsByCurrency = $this->calculateLaborCostsForMonthByCurrency($project, $assignments, $monthStart, $monthEnd);
            foreach ($projectLaborCostsByCurrency as $currency => $cost) {
                if (!isset($laborCostsByCurrency[$currency])) {
                    $laborCostsByCurrency[$currency] = 0;
                }
                $laborCostsByCurrency[$currency] += $cost;
            }
            
            // Calculate variable costs by currency
            $projectVariableCostsByCurrency = $this->calculateVariableCostsByCurrency($variableCosts);
            foreach ($projectVariableCostsByCurrency as $currency => $cost) {
                if (!isset($variableCostsByCurrency[$currency])) {
                    $variableCostsByCurrency[$currency] = 0;
                }
                $variableCostsByCurrency[$currency] += $cost;
            }
        }
        
        // Get fixed costs for the month (where period overlaps with month) - grouped by currency
        $fixedCosts = FixedCost::where(function ($query) use ($monthStart, $monthEnd) {
            $query->where(function ($q) use ($monthStart, $monthEnd) {
                $q->where('start_date', '<=', $monthEnd)
                  ->where(function ($q2) use ($monthStart) {
                      $q2->whereNull('end_date')
                         ->orWhere('end_date', '>=', $monthStart);
                  });
            });
        })->get();
        
        $fixedCostsByCurrency = $this->calculateFixedCostsForMonthByCurrency($fixedCosts, $monthStart, $monthEnd);
        
        // Round all values
        foreach ($revenueByCurrency as $currency => $value) {
            $revenueByCurrency[$currency] = round($value, 2);
        }
        foreach ($laborCostsByCurrency as $currency => $value) {
            $laborCostsByCurrency[$currency] = round($value, 2);
        }
        foreach ($variableCostsByCurrency as $currency => $value) {
            $variableCostsByCurrency[$currency] = round($value, 2);
        }
        foreach ($fixedCostsByCurrency as $currency => $value) {
            $fixedCostsByCurrency[$currency] = round($value, 2);
        }
        
        return [
            'revenue_by_currency' => $revenueByCurrency,
            'labor_costs_by_currency' => $laborCostsByCurrency,
            'variable_costs_by_currency' => $variableCostsByCurrency,
            'fixed_costs_by_currency' => $fixedCostsByCurrency,
        ];
    }

    /**
     * Calculate fixed costs for a specific month.
     * For costs that span multiple months, calculate proportional amount.
     */
    protected function calculateFixedCostsForMonth($fixedCosts, Carbon $monthStart, Carbon $monthEnd): float
    {
        $totalCost = 0;
        $daysInMonth = $monthStart->diffInDays($monthEnd) + 1;

        foreach ($fixedCosts as $fixedCost) {
            $costStart = Carbon::parse($fixedCost->start_date);
            $costEnd = $fixedCost->end_date ? Carbon::parse($fixedCost->end_date) : $monthEnd;
            
            // Calculate overlap period
            $periodStart = $costStart->gt($monthStart) ? $costStart : $monthStart;
            $periodEnd = $costEnd->lt($monthEnd) ? $costEnd : $monthEnd;
            
            if ($periodStart->gt($periodEnd)) {
                continue; // No overlap
            }
            
            // Calculate proportion
            $costDays = $costStart->diffInDays($costEnd) + 1;
            $overlapDays = $periodStart->diffInDays($periodEnd) + 1;
            
            if ($costDays > 0) {
                $proportion = $overlapDays / $costDays;
                $totalCost += (float) $fixedCost->amount * $proportion;
            } else {
                $totalCost += (float) $fixedCost->amount;
            }
        }

        return $totalCost;
    }

    /**
     * Calculate fixed costs for a specific month, grouped by currency.
     * For costs that span multiple months, calculate proportional amount.
     */
    protected function calculateFixedCostsForMonthByCurrency($fixedCosts, Carbon $monthStart, Carbon $monthEnd): array
    {
        $costsByCurrency = [];
        $daysInMonth = $monthStart->diffInDays($monthEnd) + 1;

        foreach ($fixedCosts as $fixedCost) {
            $costStart = Carbon::parse($fixedCost->start_date);
            $costEnd = $fixedCost->end_date ? Carbon::parse($fixedCost->end_date) : $monthEnd;
            $currency = $fixedCost->currency ?? 'EUR';
            
            // Calculate overlap period
            $periodStart = $costStart->gt($monthStart) ? $costStart : $monthStart;
            $periodEnd = $costEnd->lt($monthEnd) ? $costEnd : $monthEnd;
            
            if ($periodStart->gt($periodEnd)) {
                continue; // No overlap
            }
            
            // Calculate proportion
            $costDays = $costStart->diffInDays($costEnd) + 1;
            $overlapDays = $periodStart->diffInDays($periodEnd) + 1;
            
            if ($costDays > 0) {
                $proportion = $overlapDays / $costDays;
                $cost = (float) $fixedCost->amount * $proportion;
            } else {
                $cost = (float) $fixedCost->amount;
            }
            
            if (!isset($costsByCurrency[$currency])) {
                $costsByCurrency[$currency] = 0;
            }
            $costsByCurrency[$currency] += $cost;
        }

        // Round all values
        foreach ($costsByCurrency as $currency => $cost) {
            $costsByCurrency[$currency] = round($cost, 2);
        }

        return $costsByCurrency;
    }
}
