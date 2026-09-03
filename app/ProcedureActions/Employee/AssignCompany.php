<?php

namespace App\ProcedureActions\Employee;

use App\Models\Company;
use App\Models\ProcedureRun;
use App\Models\User;
use App\ProcedureActions\AbstractAction;
use App\Services\CompanyAssignmentService;
use Carbon\Carbon;
use RuntimeException;

class AssignCompany extends AbstractAction
{
    public function key(): string
    {
        return 'employee.assign_company';
    }

    public function label(): string
    {
        return 'Przypisz do spółki';
    }

    public function subjectTypes(): array
    {
        return ['employee'];
    }

    public function fields(ProcedureRun $run): array
    {
        $options = Company::query()->orderBy('name')->get()
            ->map(fn (Company $company) => ['value' => (string) $company->id, 'label' => $company->name])
            ->all();

        return [
            ['name' => 'company_id', 'label' => 'Spółka', 'type' => 'select', 'required' => true, 'options' => $options],
            ['name' => 'start_date', 'label' => 'Od', 'type' => 'date', 'required' => true],
            ['name' => 'end_date', 'label' => 'Do', 'type' => 'date'],
            ['name' => 'notes', 'label' => 'Notatka', 'type' => 'textarea'],
        ];
    }

    public function execute(ProcedureRun $run, array $payload, User $actor): array
    {
        $companyId = (int) ($payload['company_id'] ?? 0);
        $company = Company::query()->find($companyId);
        if (! $company) {
            throw new RuntimeException('Wybierz spółkę.');
        }

        $start = Carbon::parse($this->string($payload, 'start_date', true));
        $endRaw = $this->string($payload, 'end_date');
        $end = $endRaw ? Carbon::parse($endRaw) : null;

        $assignment = app(CompanyAssignmentService::class)->createAssignment(
            $this->employee($run),
            $company,
            $start,
            $end,
            $this->string($payload, 'notes'),
        );

        return ['company_assignment_id' => $assignment->id];
    }
}
