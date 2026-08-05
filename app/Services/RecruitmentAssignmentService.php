<?php

namespace App\Services;

use App\Enums\RecruitmentStatus;
use App\Models\RecruitmentProcess;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RecruitmentAssignmentService
{
    /**
     * Statuses eligible for workload distribution (all active pipeline steps).
     *
     * @return array<int, RecruitmentStatus>
     */
    public function assignableStatuses(): array
    {
        return [
            RecruitmentStatus::Nowy,
            RecruitmentStatus::WTrakcieKontaktu,
            RecruitmentStatus::Zaakceptowany,
            RecruitmentStatus::Onboarding,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function assignableStatusValues(): array
    {
        return array_map(fn (RecruitmentStatus $s) => $s->value, $this->assignableStatuses());
    }

    /**
     * Unassigned process counts per assignable status.
     *
     * @return Collection<string, int> status value => count
     */
    public function getUnassignedCountsByStatus(): Collection
    {
        $counts = RecruitmentProcess::query()
            ->whereNull('assigned_recruiter_id')
            ->whereIn('status', $this->assignableStatusValues())
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect($this->assignableStatusValues())
            ->mapWithKeys(fn (string $status) => [$status => (int) ($counts[$status] ?? 0)]);
    }

    /**
     * Assigned process counts per assignable status for a given recruiter.
     *
     * @return Collection<string, int> status value => count
     */
    public function getAssignedCountsByStatus(int $userId): Collection
    {
        $counts = RecruitmentProcess::query()
            ->where('assigned_recruiter_id', $userId)
            ->whereIn('status', $this->assignableStatusValues())
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect($this->assignableStatusValues())
            ->mapWithKeys(fn (string $status) => [$status => (int) ($counts[$status] ?? 0)]);
    }

    /**
     * Users with at least one assigned process in assignable statuses.
     *
     * @return Collection<int, array{id: int, name: string, total: int}>
     */
    public function getRecruitersWithAssignments(): Collection
    {
        return User::query()
            ->select(['users.id', 'users.name'])
            ->selectRaw('COUNT(recruitment_processes.id) as total')
            ->join('recruitment_processes', 'recruitment_processes.assigned_recruiter_id', '=', 'users.id')
            ->whereIn('recruitment_processes.status', $this->assignableStatusValues())
            ->groupBy('users.id', 'users.name')
            ->orderBy('users.name')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => $row->name,
                'total' => (int) $row->total,
            ]);
    }

    /**
     * Fetch process IDs for distribution (FIFO: oldest first).
     *
     * @param  array<int, string>  $statuses
     * @return array<int, int>
     */
    public function fetchProcessIds(array $statuses, ?int $fromRecruiterId = null, ?int $limit = null): array
    {
        $validStatuses = array_intersect($statuses, $this->assignableStatusValues());

        if ($validStatuses === []) {
            return [];
        }

        $query = RecruitmentProcess::query()
            ->whereIn('status', $validStatuses)
            ->orderBy('created_at')
            ->orderBy('id');

        if ($fromRecruiterId === null) {
            $query->whereNull('assigned_recruiter_id');
        } else {
            $query->where('assigned_recruiter_id', $fromRecruiterId);
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->pluck('id')->all();
    }

    /**
     * Evenly split a total across N recruiters; remainder goes to first recruiters.
     *
     * @param  array<int, int>  $recruiterIds
     * @return array<int, int> recruiterId => count
     */
    public function calculateEvenDistribution(int $total, array $recruiterIds): array
    {
        if ($recruiterIds === [] || $total <= 0) {
            return [];
        }

        $count = count($recruiterIds);
        $base = intdiv($total, $count);
        $remainder = $total % $count;

        $distribution = [];
        foreach ($recruiterIds as $index => $recruiterId) {
            $distribution[$recruiterId] = $base + ($index < $remainder ? 1 : 0);
        }

        return $distribution;
    }

    /**
     * @param  array<int, int>  $processIds
     * @param  array<int, int>  $distribution  recruiterId => count
     * @return array{assigned: int, by_recruiter: array<int, int>}
     */
    public function distribute(array $processIds, array $distribution, int $changedBy): array
    {
        if ($processIds === []) {
            throw new InvalidArgumentException('Brak procesów do przypisania.');
        }

        if ($distribution === []) {
            throw new InvalidArgumentException('Wybierz co najmniej jednego rekrutera.');
        }

        $expectedTotal = count($processIds);
        $distributionTotal = array_sum($distribution);

        if ($distributionTotal !== $expectedTotal) {
            throw new InvalidArgumentException(
                "Suma przypisań ({$distributionTotal}) musi równać się liczbie procesów ({$expectedTotal})."
            );
        }

        foreach (array_keys($distribution) as $recruiterId) {
            if (! User::whereKey($recruiterId)->exists()) {
                throw new InvalidArgumentException("Nieprawidłowy rekruter (ID: {$recruiterId}).");
            }
        }

        $byRecruiter = [];
        $offset = 0;

        return DB::transaction(function () use ($processIds, $distribution, $changedBy, &$byRecruiter, &$offset) {
            foreach ($distribution as $recruiterId => $count) {
                if ($count <= 0) {
                    continue;
                }

                $slice = array_slice($processIds, $offset, $count);
                $offset += $count;

                $processes = RecruitmentProcess::whereIn('id', $slice)->get();
                $assigned = 0;

                foreach ($processes as $process) {
                    $process->assignRecruiter((int) $recruiterId, $changedBy);
                    $assigned++;
                }

                $byRecruiter[(int) $recruiterId] = $assigned;
            }

            return [
                'assigned' => array_sum($byRecruiter),
                'by_recruiter' => $byRecruiter,
            ];
        });
    }
}
