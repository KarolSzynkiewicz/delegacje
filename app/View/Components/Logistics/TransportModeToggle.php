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

    /** @var 'airport'|'station'|null — przy transporcie publicznym: podpis przycisku (samolot / bus) zamiast „Publiczny”. */
    public ?string $hubKind;

    public string $publicButtonIcon;

    public string $publicButtonLabel;

    public string $publicButtonTitle;

    public function __construct(?string $mode = null, bool $required = true, bool $interactive = true, ?string $hubKind = null)
    {
        $this->mode = $mode === 'own' ? 'own' : ($mode === 'public' ? 'public' : null);
        $this->required = $required;
        $this->interactive = $interactive;
        $this->hubKind = $hubKind === 'station' ? 'station' : ($hubKind === 'airport' ? 'airport' : null);

        $publicActive = $this->mode === 'public';
        $hubSelected = $publicActive && $this->hubKind !== null;
        if ($hubSelected && $this->hubKind === 'airport') {
            $this->publicButtonIcon = 'bi-airplane';
            $this->publicButtonLabel = 'Samolot';
            $this->publicButtonTitle = 'Zmień typ punktu (lotnisko / dworzec)';
        } elseif ($hubSelected && $this->hubKind === 'station') {
            $this->publicButtonIcon = 'bi-train-front';
            $this->publicButtonLabel = 'Bus / pociąg';
            $this->publicButtonTitle = 'Zmień typ punktu (lotnisko / dworzec)';
        } else {
            $this->publicButtonIcon = 'bi-airplane';
            $this->publicButtonLabel = 'Publiczny';
            $this->publicButtonTitle = 'Transport publiczny — lotnisko lub dworzec';
        }
    }

    public function render(): View
    {
        return view('components.logistics.transport-mode-toggle');
    }
}
