<?php

namespace App\Livewire\Concerns;

/**
 * Wspólna obsługa przełączania Publiczny / Własny (modal potwierdzenia + {@see setTransportMode}).
 * Komponent musi definiować właściwości: transportMode, pendingTransportMode, showTransportSwitchModal
 * oraz zaimplementować hooki czyszczenia stanu przy zmianie trybu.
 *
 * @property 'public'|'own'|null $transportMode
 * @property 'public'|'own'|null $pendingTransportMode
 * @property bool $showTransportSwitchModal
 */
trait InteractsWithLogisticsTransportMode
{
    public function requestSetTransportMode(string $mode): void
    {
        $mode = $mode === 'own' ? 'own' : 'public';
        if ($mode === $this->transportMode) {
            return;
        }
        if ($this->transportMode === null) {
            $this->setTransportMode($mode);

            return;
        }
        $this->pendingTransportMode = $mode;
        $this->showTransportSwitchModal = true;
    }

    public function confirmTransportModeSwitch(): void
    {
        if ($this->pendingTransportMode === null) {
            return;
        }
        $mode = $this->pendingTransportMode;
        $this->showTransportSwitchModal = false;
        $this->pendingTransportMode = null;
        $this->setTransportMode($mode);
    }

    public function cancelTransportModeSwitch(): void
    {
        $this->showTransportSwitchModal = false;
        $this->pendingTransportMode = null;
    }

    /**
     * Przycisk „Publiczny” / „Samolot” / „Bus / pociąg”: pierwszy raz wybiera transport publiczny;
     * gdy typ huba jest już wybrany — reset (jak dawny link „Zmień typ”).
     */
    public function requestPublicTransportModeButtonAction(): void
    {
        if ($this->transportMode !== 'public') {
            $this->requestSetTransportMode('public');

            return;
        }
        if (! property_exists($this, 'publicTransportHubKind')) {
            return;
        }
        if ($this->publicTransportHubKind === null) {
            return;
        }
        $this->resetPublicTransportHubSelection();
    }

    abstract public function resetPublicTransportHubSelection(): void;

    public function setTransportMode(string $mode): void
    {
        $mode = $mode === 'own' ? 'own' : 'public';
        if ($mode === $this->transportMode) {
            return;
        }

        if ($mode === 'public') {
            $this->onSwitchingToPublicTransportMode();
        } else {
            $this->onSwitchingToOwnTransportMode();
        }

        $this->transportMode = $mode;
        $this->afterTransportModeChanged($mode);
    }

    /**
     * Stan związany z własnym pojazdem (wyjazd: dojazdy, trasa, transfer w nagłówku; zjazd: pojazd pusty + lotniska).
     */
    abstract protected function onSwitchingToPublicTransportMode(): void;

    /**
     * Przejście na transport własny: czyszczenie lotnisk/biletów i ewentualnie pierwsze auto + init miejsc.
     */
    abstract protected function onSwitchingToOwnTransportMode(): void;

    /**
     * Wywoływane po ustawieniu {@see $transportMode} (np. zjazd: zerowanie podglądu).
     */
    protected function afterTransportModeChanged(string $mode): void
    {
        // domyślnie nic — nadpisz w komponencie jeśli potrzeba
    }
}
