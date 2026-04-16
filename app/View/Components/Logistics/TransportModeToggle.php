<?php

namespace App\View\Components\Logistics;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TransportModeToggle extends Component
{
    /** @var 'public'|'own'|null */
    public ?string $mode;

    public bool $required;

    /** Katalog / welcome2: bez wire:click (brak rodzica Livewire). */
    public bool $interactive;

    public function __construct(?string $mode = null, bool $required = true, bool $interactive = true)
    {
        $this->mode = $mode === 'own' ? 'own' : ($mode === 'public' ? 'public' : null);
        $this->required = $required;
        $this->interactive = $interactive;
    }

    public function render(): View
    {
        return view('components.logistics.transport-mode-toggle');
    }
}
