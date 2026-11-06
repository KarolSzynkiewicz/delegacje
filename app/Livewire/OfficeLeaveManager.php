<?php

namespace App\Livewire;

use App\Models\OfficeLeaveAdjustment;
use App\Models\OfficeProfile;
use App\Models\User;
use App\Services\OfficeLeaveBalanceService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class OfficeLeaveManager extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url]
    public int $year = 0;

    public ?int $selectedProfileId = null;

    public string $days = '1';

    public string $reason = '';

    public string $effectiveOn = '';

    public function mount(): void
    {
        $this->year = $this->year !== 0 ? $this->year : (int) now()->year;
        $this->effectiveOn = now()->toDateString();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedYear(): void
    {
        $this->year = max(2000, min(2100, (int) $this->year));
        $this->resetPage();
        $this->resetForm();
    }

    public function selectProfile(int $profileId): void
    {
        $this->selectedProfileId = $profileId;
        $this->resetForm(keepProfile: true);
    }

    public function clearSelection(): void
    {
        $this->selectedProfileId = null;
        $this->resetForm();
    }

    public function addAdjustment(): void
    {
        if (! $this->canManage()) {
            return;
        }

        $this->validate([
            'selectedProfileId' => ['required', 'exists:office_profiles,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'days' => ['required', 'integer', 'not_in:0', 'min:-366', 'max:366'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
            'effectiveOn' => ['nullable', 'date'],
        ], [
            'days.not_in' => 'Podaj liczbę dni różną od zera (+ dodaje, − odejmuje).',
            'reason.required' => 'Podaj powód korekty (audyt).',
        ], [
            'selectedProfileId' => 'profil',
            'days' => 'dni',
            'reason' => 'powód',
            'effectiveOn' => 'data',
        ]);

        OfficeLeaveAdjustment::query()->create([
            'office_profile_id' => $this->selectedProfileId,
            'year' => $this->year,
            'days' => (int) $this->days,
            'reason' => trim($this->reason),
            'effective_on' => $this->effectiveOn !== '' ? $this->effectiveOn : now()->toDateString(),
            'created_by' => Auth::id(),
        ]);

        $this->resetForm(keepProfile: true);
        $this->dispatch('office-leave-adjusted');
    }

    public function deleteAdjustment(int $id): void
    {
        if (! $this->canManage()) {
            return;
        }

        $row = OfficeLeaveAdjustment::query()->find($id);
        if (! $row) {
            return;
        }

        $profileId = (int) $row->office_profile_id;
        $row->delete();

        if ($this->selectedProfileId === $profileId) {
            // stay on profile so list refreshes
        }
    }

    protected function canManage(): bool
    {
        $user = Auth::user();

        return $user && (
            $user->isAdmin()
            || $user->hasPermission('office-leave.update')
            || $user->hasPermission('office-leave.create')
            || $user->hasPermission('office-profiles.update')
        );
    }

    protected function resetForm(bool $keepProfile = false): void
    {
        if (! $keepProfile) {
            $this->selectedProfileId = null;
        }
        $this->days = '1';
        $this->reason = '';
        $this->effectiveOn = now()->toDateString();
        $this->resetValidation();
    }

    public function render(OfficeLeaveBalanceService $balance)
    {
        $profiles = OfficeProfile::query()
            ->with('user:id,name,email')
            ->where('is_active', true)
            ->when($this->search !== '', function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->whereHas('user', function ($uq) use ($term) {
                    $uq->where('name', 'like', $term)->orWhere('email', 'like', $term);
                });
            })
            ->orderBy(
                User::query()
                    ->select('name')
                    ->whereColumn('users.id', 'office_profiles.user_id')
                    ->limit(1)
            )
            ->paginate(20);

        $rows = $profiles->getCollection()->map(function (OfficeProfile $profile) use ($balance) {
            $summary = $balance->forProfile($profile, $this->year);

            return [
                'profile' => $profile,
                'balance' => $summary,
            ];
        });

        $profiles->setCollection($rows);

        $selected = null;
        $selectedBalance = null;
        if ($this->selectedProfileId) {
            $selected = OfficeProfile::query()
                ->with('user:id,name,email')
                ->find($this->selectedProfileId);
            if ($selected) {
                $selectedBalance = $balance->forProfile($selected, $this->year);
            }
        }

        return view('livewire.office-leave-manager', [
            'profiles' => $profiles,
            'selected' => $selected,
            'selectedBalance' => $selectedBalance,
            'canManage' => $this->canManage(),
        ]);
    }
}
