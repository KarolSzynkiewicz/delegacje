import './bootstrap';
import 'bootstrap'; // Bootstrap JS (dla modals, dropdowns, etc.)

/**
 * NIE przenosimy `.modal-portal-to-body` na document.body.
 * appendChild(portal) wyrywa modal z drzewa DOM komponentu Livewire — wtedy
 * `wire:click` / `closest('[wire:id]')` nie znajduje komponentu i przestają
 * działać zamykanie modala, nawigacja miesiąca i wybór dat w kalendarzu.
 * Pozycjonowanie względem viewportu jest obsłużone w CSS (fixed + z-index).
 */

// Alpine.js jest już ładowany przez Livewire, więc nie trzeba go importować tutaj
// import Alpine from 'alpinejs';
// window.Alpine = Alpine;
// Alpine.start();
