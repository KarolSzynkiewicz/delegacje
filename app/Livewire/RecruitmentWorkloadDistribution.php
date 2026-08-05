<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\RecruitmentAssignmentService;
use InvalidArgumentException;
use Livewire\Component;

class RecruitmentWorkloadDistribution extends Component
{
    public bool $show = false;

    /** 'unassigned' | 'vacation' */
    public string $mode = 'unassigned';

    /** 1 = statuses, 2 = recruiters, 3 = confirm */
    public int $step = 1;

    /** @var array<int, string> */
    public array $selectedStatuses = [];

    public ?int $fromRecruiterId = null;

    /** @var array<int, int> */
    public array $selectedRecruiterIds = [];

    /** @var array<int, int> recruiterId => count */
    public array $distribution = [];

    public ?string $errorMessage = null;

    public ?array $result = null;

    public function openModal(): void
    {
        $this->resetForm();
        $this->show = true;
    }

    public function closeModal(): void
    {
        $this->show = false;
        $this->resetForm();
    }

    public function setMode(string $mode): void
    {
        if (! in_array($mode, ['unassigned', 'vacation'], true)) {
            return;
        }

        $this->mode = $mode;
        $this->step = 1;
        $this->selectedStatuses = [];
        $this->fromRecruiterId = null;
        $this->selectedRecruiterIds = [];
        $this->distribution = [];
        $this->errorMessage = null;
        $this->result = null;
    }

    public function toggleStatus(string $status): void
    {
        if (in_array($status, $this->selectedStatuses, true)) {
            $this->selectedStatuses = array_values(array_filter(
                $this->selectedStatuses,
                fn ($s) => $s !== $status
            ));
        } else {
            $this->selectedStatuses[] = $status;
        }
    }

    public function goToStep(int $step): void
    {
        if ($step < 1 || $step > 3) {
            return;
        }

        if ($step === 2 && ! $this->validateStep1()) {
            return;
        }

        if ($step === 3 && ! $this->validateStep2()) {
            return;
        }

        $this->step = $step;
        $this->errorMessage = null;
    }

    public function nextStep(): void
    {
        if ($this->step === 1 && $this->validateStep1()) {
            $this->step = 2;
            $this->errorMessage = null;
        } elseif ($this->step === 2 && $this->validateStep2()) {
            $this->step = 3;
            $this->errorMessage = null;
        }
    }

    public function previousStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
            $this->errorMessage = null;
        }
    }

    public function updatedFromRecruiterId(): void
    {
        $this->selectedStatuses = [];
        $this->selectedRecruiterIds = [];
        $this->distribution = [];
    }

    public function toggleRecruiter(int $recruiterId): void
    {
        if (in_array($recruiterId, $this->selectedRecruiterIds, true)) {
            $this->selectedRecruiterIds = array_values(array_filter(
                $this->selectedRecruiterIds,
                fn ($id) => $id !== $recruiterId
            ));
        } else {
            $this->selectedRecruiterIds[] = $recruiterId;
        }

        $this->recalculateDistribution();
    }

    public function updatedDistribution(): void
    {
        // Normalize empty strings to 0
        foreach ($this->distribution as $id => $count) {
            $this->distribution[$id] = max(0, (int) $count);
        }
    }

    public function recalculateDistribution(): void
    {
        $total = $this->getSelectedTotal();

        if ($this->selectedRecruiterIds === [] || $total === 0) {
            $this->distribution = [];

            return;
        }

        $this->distribution = app(RecruitmentAssignmentService::class)
            ->calculateEvenDistribution($total, $this->selectedRecruiterIds);
    }

    public function confirmDistribution(): void
    {
        if (! $this->validateStep2()) {
            return;
        }

        $service = app(RecruitmentAssignmentService::class);

        try {
            $processIds = $service->fetchProcessIds(
                $this->selectedStatuses,
                $this->mode === 'vacation' ? $this->fromRecruiterId : null
            );

            $result = $service->distribute(
                $processIds,
                $this->distribution,
                auth()->id()
            );

            $recruiterNames = User::whereIn('id', array_keys($result['by_recruiter']))
                ->pluck('name', 'id');

            $this->result = [
                'assigned' => $result['assigned'],
                'by_recruiter' => collect($result['by_recruiter'])
                    ->map(fn ($count, $id) => [
                        'name' => $recruiterNames[$id] ?? '—',
                        'count' => $count,
                    ])
                    ->values()
                    ->all(),
            ];

            $this->dispatch('workload-distributed');
        } catch (InvalidArgumentException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function getSelectedTotal(): int
    {
        $service = app(RecruitmentAssignmentService::class);

        if ($this->selectedStatuses === []) {
            return 0;
        }

        if ($this->mode === 'vacation') {
            if (! $this->fromRecruiterId) {
                return 0;
            }

            $counts = $service->getAssignedCountsByStatus($this->fromRecruiterId);

            return (int) collect($this->selectedStatuses)->sum(fn ($s) => $counts[$s] ?? 0);
        }

        $counts = $service->getUnassignedCountsByStatus();

        return (int) collect($this->selectedStatuses)->sum(fn ($s) => $counts[$s] ?? 0);
    }

    public function getDistributionSum(): int
    {
        return array_sum($this->distribution);
    }

    public function getDistributionValidProperty(): bool
    {
        $total = $this->getSelectedTotal();

        return $total > 0
            && $this->selectedRecruiterIds !== []
            && $this->getDistributionSum() === $total;
    }

    private function validateStep1(): bool
    {
        if ($this->selectedStatuses === []) {
            $this->errorMessage = 'Wybierz co najmniej jeden status.';

            return false;
        }

        if ($this->mode === 'vacation' && ! $this->fromRecruiterId) {
            $this->errorMessage = 'Wybierz rekrutera na urlopie.';

            return false;
        }

        if ($this->getSelectedTotal() === 0) {
            $this->errorMessage = 'Brak procesów do podziału dla wybranych statusów.';

            return false;
        }

        return true;
    }

    private function validateStep2(): bool
    {
        if (! $this->validateStep1()) {
            return false;
        }

        if ($this->selectedRecruiterIds === []) {
            $this->errorMessage = 'Wybierz co najmniej jednego rekrutera docelowego.';

            return false;
        }

        if ($this->mode === 'vacation' && in_array($this->fromRecruiterId, $this->selectedRecruiterIds, true)) {
            $this->errorMessage = 'Rekruter na urlopie nie może być wśród osób docelowych.';

            return false;
        }

        $total = $this->getSelectedTotal();
        $sum = $this->getDistributionSum();

        if ($sum !== $total) {
            $this->errorMessage = "Suma przypisań ({$sum}) musi równać się liczbie procesów ({$total}).";

            return false;
        }

        return true;
    }

    private function resetForm(): void
    {
        $this->reset([
            'mode',
            'step',
            'selectedStatuses',
            'fromRecruiterId',
            'selectedRecruiterIds',
            'distribution',
            'errorMessage',
            'result',
        ]);
        $this->mode = 'unassigned';
        $this->step = 1;
    }

    public function render()
    {
        $service = app(RecruitmentAssignmentService::class);

        return view('livewire.recruitment-workload-distribution', [
            'assignableStatuses' => $service->assignableStatuses(),
            'unassignedCounts' => $service->getUnassignedCountsByStatus(),
            'assignedCounts' => $this->fromRecruiterId
                ? $service->getAssignedCountsByStatus($this->fromRecruiterId)
                : collect(),
            'recruitersOnLeave' => $service->getRecruitersWithAssignments(),
            'recruiters' => User::orderBy('name')->get(),
            'selectedTotal' => $this->getSelectedTotal(),
            'distributionSum' => $this->getDistributionSum(),
        ]);
    }
}
