<?php

namespace App\Livewire;

use App\Services\EmployeeCandidateHireSyncService;
use Livewire\Component;

class EmployeeCandidateHireSync extends Component
{
    public bool $show = false;

    /** @var list<array<string, mixed>> */
    public array $preview = [];

    public ?array $applyResult = null;

    public ?string $error = null;

    public function openModal(): void
    {
        $this->reset(['preview', 'applyResult', 'error']);
        $this->show = true;
        $this->loadPreview();
    }

    public function closeModal(): void
    {
        $this->show = false;
        $this->reset(['preview', 'applyResult', 'error']);
    }

    public function loadPreview(): void
    {
        $this->applyResult = null;
        $this->error = null;

        try {
            $this->preview = app(EmployeeCandidateHireSyncService::class)
                ->preview()
                ->values()
                ->all();
        } catch (\Throwable $e) {
            $this->error = 'Błąd podglądu: '.$e->getMessage();
            $this->preview = [];
        }
    }

    public function doApply(): void
    {
        if (empty($this->preview)) {
            return;
        }

        $this->error = null;

        try {
            $this->applyResult = app(EmployeeCandidateHireSyncService::class)
                ->apply(collect($this->preview));
            $this->preview = [];
        } catch (\Throwable $e) {
            $this->error = 'Błąd synchronizacji: '.$e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.employee-candidate-hire-sync');
    }
}
