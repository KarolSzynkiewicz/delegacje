<?php

namespace App\Livewire;

use Livewire\Component;

class RotationsWorkspace extends Component
{
    public string $view = 'axis';

    protected $queryString = [
        'view' => ['except' => 'axis'],
    ];

    public function mount(): void
    {
        $requested = request()->query('view');
        if (in_array($requested, ['axis', 'table'], true)) {
            $this->view = $requested;
            session(['rotations_view' => $requested]);

            return;
        }

        $saved = session('rotations_view');
        if (in_array($saved, ['axis', 'table'], true)) {
            $this->view = $saved;
        }
    }

    public function setView(string $view): void
    {
        if (! in_array($view, ['axis', 'table'], true)) {
            return;
        }

        if ($this->view === $view) {
            return;
        }

        $this->view = $view;
        session(['rotations_view' => $view]);
    }

    public function render()
    {
        return view('livewire.rotations-workspace');
    }
}
