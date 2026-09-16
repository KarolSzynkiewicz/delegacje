<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectSiteLead;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectSiteLeadService
{
    /**
     * @throws ValidationException
     */
    public function assign(
        Project $project,
        Employee $employee,
        Carbon $startDate,
        ?Carbon $endDate = null,
        ?string $notes = null
    ): ProjectSiteLead {
        $startDate = DateRangeService::normalizeDate($startDate);
        $endDate = $endDate ? DateRangeService::normalizeDate($endDate) : null;

        return DB::transaction(function () use ($project, $employee, $startDate, $endDate, $notes) {
            $this->closePreviousOpenLead($project, $startDate);
            $this->assertNoOverlap($project, $startDate, $endDate);

            return ProjectSiteLead::query()->create([
                'project_id' => $project->id,
                'employee_id' => $employee->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * @throws ValidationException
     */
    public function update(
        ProjectSiteLead $lead,
        Employee $employee,
        Carbon $startDate,
        ?Carbon $endDate = null,
        ?string $notes = null
    ): ProjectSiteLead {
        $startDate = DateRangeService::normalizeDate($startDate);
        $endDate = $endDate ? DateRangeService::normalizeDate($endDate) : null;

        return DB::transaction(function () use ($lead, $employee, $startDate, $endDate, $notes) {
            $this->assertNoOverlap($lead->project, $startDate, $endDate, $lead->id);

            $lead->update([
                'employee_id' => $employee->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'notes' => $notes,
            ]);

            return $lead->fresh(['employee', 'project']);
        });
    }

    public function delete(ProjectSiteLead $lead): void
    {
        $lead->delete();
    }

    /**
     * Set the weekly planner lead, replacing anyone who overlaps that week.
     *
     * @throws ValidationException
     */
    public function replaceForWeek(Project $project, Employee $employee, Carbon $weekStart): ProjectSiteLead
    {
        $weekStart = DateRangeService::normalizeDate($weekStart)->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        $isAssignedThisWeek = $project->assignments()
            ->overlappingWith($weekStart, $weekEnd)
            ->where('employee_id', $employee->id)
            ->exists();

        if (! $isAssignedThisWeek) {
            throw ValidationException::withMessages([
                'employee_id' => 'Wybierz kogoś z przypisanych w tym tygodniu.',
            ]);
        }

        return DB::transaction(function () use ($project, $employee, $weekStart) {
            $this->closeLeadsOverlappingFrom($project, $weekStart);

            return ProjectSiteLead::query()->create([
                'project_id' => $project->id,
                'employee_id' => $employee->id,
                'start_date' => $weekStart,
                'end_date' => null,
            ]);
        });
    }

    /**
     * Close or remove every lead that would overlap an open-ended period from $startDate.
     */
    protected function closeLeadsOverlappingFrom(Project $project, Carbon $startDate): void
    {
        $leads = $project->siteLeads()
            ->overlappingWith($startDate, DateRangeService::getDefaultEndDate())
            ->get();

        foreach ($leads as $lead) {
            if ($lead->getStartDate()->gte($startDate)) {
                $lead->delete();

                continue;
            }

            $lead->update(['end_date' => $startDate->copy()->subDay()]);
        }
    }

    /**
     * Close an open-ended (or still-running) previous lead the day before the new one starts.
     *
     * @throws ValidationException
     */
    protected function closePreviousOpenLead(Project $project, Carbon $startDate): void
    {
        $previous = $project->siteLeads()
            ->where('start_date', '<', $startDate->toDateString())
            ->where(function ($query) use ($startDate) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $startDate->toDateString());
            })
            ->orderByDesc('start_date')
            ->first();

        if (! $previous) {
            return;
        }

        $closeOn = $startDate->copy()->subDay();

        if ($closeOn->lt($previous->getStartDate())) {
            throw ValidationException::withMessages([
                'start_date' => 'Nie można zamknąć poprzedniego kierownika przed datą jego rozpoczęcia. Skróć lub usuń istniejący wpis.',
            ]);
        }

        $previous->update(['end_date' => $closeOn]);
    }

    /**
     * One site lead per project at any given time.
     *
     * @throws ValidationException
     */
    protected function assertNoOverlap(
        Project $project,
        Carbon $startDate,
        ?Carbon $endDate,
        ?int $excludeId = null
    ): void {
        DateRangeService::validateNoOverlappingAssignments(
            $project->siteLeads(),
            $startDate,
            $endDate ?? DateRangeService::getDefaultEndDate(),
            $excludeId,
            'start_date',
            'W tym okresie projekt ma już kierownika. Usuń lub skróć istniejący wpis.'
        );
    }
}
