<?php

namespace App\ProcedureActions\Recruitment;

use App\Enums\RecruitmentStatus;
use App\Models\Employee;
use App\Models\ProcedureRun;
use App\Models\Role;
use App\Models\User;
use App\ProcedureActions\AbstractAction;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class HireCandidate extends AbstractAction
{
    public function key(): string
    {
        return 'recruitment.hire';
    }

    public function label(): string
    {
        return 'Zatrudnij kandydata';
    }

    public function subjectTypes(): array
    {
        return ['recruitment_candidate', 'recruitment_process'];
    }

    public function fields(ProcedureRun $run): array
    {
        $options = Role::query()->orderBy('name')->get()
            ->map(fn (Role $role) => ['value' => (string) $role->id, 'label' => $role->name])
            ->all();

        return [
            [
                'name' => 'roles',
                'label' => 'Role pracownika',
                'type' => 'multiselect',
                'required' => true,
                'options' => $options,
            ],
        ];
    }

    public function execute(ProcedureRun $run, array $payload, User $actor): array
    {
        $process = $this->process($run);
        if ($process->employee_id) {
            throw new RuntimeException('Ten kandydat jest już zatrudniony.');
        }

        $candidate = $this->candidate($run);
        $roleIds = array_values(array_filter(array_map('intval', (array) ($payload['roles'] ?? []))));
        if ($roleIds === []) {
            throw new RuntimeException('Wybierz przynajmniej jedną rolę pracownika.');
        }

        $existing = Role::query()->whereIn('id', $roleIds)->pluck('id')->all();
        if (count($existing) !== count($roleIds)) {
            throw new RuntimeException('Wybrana rola nie istnieje.');
        }

        $imagePath = null;
        if ($candidate->photo_path) {
            $newPath = 'employees/'.basename($candidate->photo_path);
            if (Storage::disk('public')->exists($candidate->photo_path)) {
                Storage::disk('public')->copy($candidate->photo_path, $newPath);
            }
            $imagePath = $newPath;
        }

        $employee = Employee::query()->create([
            'first_name' => $candidate->first_name,
            'last_name' => $candidate->last_name,
            'email' => $candidate->email,
            'phone' => $candidate->phone,
            'notes' => null,
            'image_path' => $imagePath,
        ]);

        $employee->roles()->attach($roleIds);
        $candidate->update(['employee_id' => $employee->id]);
        $process->transitionTo(RecruitmentStatus::Zatrudniony, $actor->id);
        $process->update(['employee_id' => $employee->id]);

        return ['employee_id' => $employee->id];
    }
}
