<?php

namespace App\Services;

use App\Enums\RoleSeniority;
use App\Models\Employee;
use App\Models\EmployeeRoleSeniorityChange;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeRoleSeniorityService
{
    /**
     * @param  array<int|string>  $roleIds
     * @param  array<int, int|string|null>  $seniorityByRoleId
     */
    public function syncRoles(Employee $employee, array $roleIds, array $seniorityByRoleId = [], ?User $actor = null): void
    {
        $roleIds = collect($roleIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $existing = $employee->roles()->get()->keyBy('id');

        $sync = [];
        foreach ($roleIds as $roleId) {
            if (array_key_exists($roleId, $seniorityByRoleId) || array_key_exists((string) $roleId, $seniorityByRoleId)) {
                $incoming = $this->normalize($seniorityByRoleId[$roleId] ?? $seniorityByRoleId[(string) $roleId] ?? null);
            } elseif ($existing->has($roleId)) {
                $incoming = $this->normalize($existing->get($roleId)?->pivot?->seniority);
            } else {
                $incoming = null;
            }

            $sync[$roleId] = ['seniority' => $incoming];
        }

        DB::transaction(function () use ($employee, $existing, $sync, $actor) {
            foreach ($sync as $roleId => $data) {
                $from = $existing->has($roleId)
                    ? $this->normalize($existing->get($roleId)?->pivot?->seniority)
                    : null;
                $to = $data['seniority'];

                if ($existing->has($roleId) && $from === $to) {
                    continue;
                }

                if (! $existing->has($roleId) && $to === null) {
                    continue;
                }

                $this->record($employee->id, (int) $roleId, $from, $to, $actor, null);
            }

            $employee->roles()->sync($sync);
        });
    }

    public function setLevel(
        Employee $employee,
        int $roleId,
        ?int $level,
        ?string $comment = null,
        ?User $actor = null
    ): void {
        $to = $this->normalize($level);

        if ($to !== null && RoleSeniority::tryFrom($to) === null) {
            throw ValidationException::withMessages([
                'seniority' => 'Nieprawidłowy poziom seniority.',
            ]);
        }

        $current = $employee->roles()->where('roles.id', $roleId)->first();

        if (! $current) {
            throw ValidationException::withMessages([
                'role_id' => 'Pracownik nie ma tego zawodu.',
            ]);
        }

        $from = $this->normalize($current->pivot->seniority);

        if ($from === $to) {
            return;
        }

        DB::transaction(function () use ($employee, $roleId, $from, $to, $comment, $actor) {
            $employee->roles()->updateExistingPivot($roleId, ['seniority' => $to]);
            $this->record($employee->id, $roleId, $from, $to, $actor, $comment);
        });
    }

    public function normalize(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }

    protected function record(
        int $employeeId,
        int $roleId,
        ?int $from,
        ?int $to,
        ?User $actor,
        ?string $comment
    ): void {
        EmployeeRoleSeniorityChange::query()->create([
            'employee_id' => $employeeId,
            'role_id' => $roleId,
            'from_seniority' => $from,
            'to_seniority' => $to,
            'changed_by' => $actor?->id ?? auth()->id(),
            'comment' => $comment !== null && $comment !== '' ? $comment : null,
            'created_at' => now(),
        ]);
    }
}
