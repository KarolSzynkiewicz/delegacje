<?php

namespace App\Livewire;

use App\Support\ChronoAssistCatalog;
use Livewire\Component;

/**
 * Reużywalny picker Chrono Assist. Rodzic otwiera go warunkiem (np. @if($showChronoModal)),
 * a reaguje na eventy: chrono-assist-picked, chrono-assist-closed.
 *
 * @param  string  $context  ChronoAssistCatalog::CONTEXT_GRID | CONTEXT_TASK — które akcje świecą.
 */
class ChronoAssist extends Component
{
    /** @var list<string|array{label: string}> */
    public array $contextChips = [];

    public ?int $itemCount = null;

    public string $title = 'Jak mogę Ci pomóc?';

    public string $status = 'Wybierz akcję dla bieżącego filtra';

    public string $contextLabel = 'Kontekst';

    public string $context = ChronoAssistCatalog::CONTEXT_GRID;

    /**
     * @var list<array<string, mixed>>|null
     */
    public ?array $actions = null;

    public function mount(?array $actions = null): void
    {
        $this->actions = $actions ?? ChronoAssistCatalog::actions();
    }

    public function pick(string $key): void
    {
        if (! ChronoAssistCatalog::shouldDispatch($key, $this->context)) {
            return;
        }

        $this->dispatch('chrono-assist-picked', key: $key);
    }

    public function close(): void
    {
        $this->dispatch('chrono-assist-closed');
    }

    public function render()
    {
        return view('livewire.chrono-assist', [
            'enabledKeys' => ChronoAssistCatalog::enabledKeys($this->context),
            'actions' => $this->actions ?? ChronoAssistCatalog::actions(),
        ]);
    }
}
