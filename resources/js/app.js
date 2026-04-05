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

/**
 * Autocomplete @wzmianki w polach Livewire (podzadania).
 * Rejestrujemy przez Alpine.data() wewnątrz `alpine:init` — to jedyne pewne okno,
 * kiedy Alpine jest dostępne, ale jeszcze nie przetworzyło x-data w DOM.
 * Używamy wire:model.defer — .live przeładowywałoby komponent przy każdym znaku i niszczyło dropdown.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('subtaskMention', (users, wireProperty) => ({
        users: users || [],
        wireProperty,
        show: false,
        results: [],
        activeIdx: 0,

        onInput(event) {
            const el = event.target;
            const pos = el.selectionStart;
            const text = el.value.substring(0, pos);
            // Trigger tylko gdy @ jest na początku lub po białym znaku
            const m = text.match(/(^|\s)@(\S*)$/u);

            if (!m) { this.close(); return; }

            const fragment = m[2];
            if (fragment.length === 0) { this.close(); return; }

            const q = fragment.toLowerCase();
            this.results = this.users.filter((u) => u.name.toLowerCase().includes(q)).slice(0, 8);
            this.activeIdx = 0;
            this.show = this.results.length > 0;
        },

        selectUser(user) {
            const el = this.$refs.inp;
            if (!el) return;

            const pos = el.selectionStart;
            const text = el.value.substring(0, pos);
            const m = text.match(/(^|\s)@(\S*)$/u);
            if (!m) return;

            const fragment = m[2];
            // Indeks @ w oryginalnym tekście (uwzględnia poprzedzający biały znak)
            const atPos = pos - fragment.length - 1;
            const before = el.value.substring(0, atPos);
            const after  = el.value.substring(pos);
            const newVal = before + '@' + user.name + ' ' + after;

            el.value = newVal;
            if (this.$wire) this.$wire.set(this.wireProperty, newVal);

            const newPos = atPos + user.name.length + 2; // '@' + name + ' '
            el.setSelectionRange(newPos, newPos);
            this.close();
            el.focus();
        },

        close() {
            this.show   = false;
            this.results = [];
        },
    }));
});
