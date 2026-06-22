<?php

namespace App\Livewire;

use App\Models\RecruitmentApplication;
use Livewire\Component;
use Livewire\WithPagination;

class RecruitmentApplicationsTable extends Component
{
    use WithPagination;

    public string $status = '';

    public string $firstName = '';

    public string $lastName = '';

    public string $phone = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    protected $queryString = [
        'status' => ['except' => ''],
        'firstName' => ['except' => '', 'as' => 'imie'],
        'lastName' => ['except' => '', 'as' => 'nazwisko'],
        'phone' => ['except' => ''],
        'sortField' => ['except' => 'created_at', 'as' => 'sort'],
        'sortDirection' => ['except' => 'desc', 'as' => 'dir'],
    ];

    public function updatingFirstName(): void
    {
        $this->resetPage();
    }

    public function updatingLastName(): void
    {
        $this->resetPage();
    }

    public function updatingPhone(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['firstName', 'lastName', 'phone']);
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        $allowed = ['created_at', 'first_name', 'last_name', 'phone', 'status', 'desired_role'];

        if (! in_array($field, $allowed, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function paginationView(): string
    {
        return 'vendor.livewire.simple-pagination';
    }

    public function render()
    {
        $query = RecruitmentApplication::query()
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->firstName, fn ($q) => $q->where('first_name', 'like', '%'.$this->firstName.'%'))
            ->when($this->lastName, fn ($q) => $q->where('last_name', 'like', '%'.$this->lastName.'%'))
            ->when($this->phone, fn ($q) => $q->where('phone', 'like', '%'.$this->phone.'%'));

        if ($this->sortField === 'status') {
            $query->orderByRaw(
                "FIELD(status, 'pending', 'reviewing', 'accepted', 'rejected', 'converted') ".($this->sortDirection === 'desc' ? 'DESC' : 'ASC')
            );
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        $applications = $query->paginate(20);

        $counts = RecruitmentApplication::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('livewire.recruitment-applications-table', [
            'applications' => $applications,
            'counts' => $counts,
        ]);
    }
}
