import './bootstrap';
import './dashboard-snaps';
import * as bootstrap from 'bootstrap'; // Bootstrap JS (dla modals, dropdowns, etc.)
window.bootstrap = bootstrap;

/**
 * ChronoLogic — globalna poświata podążająca za kursorem (ten sam ambient co na
 * probce /2 i /tasks2). Throttlowana przez requestAnimationFrame i ogranicza się
 * do przesuwania JEDNEGO elementu transformem — nie skanuje DOM przy każdym
 * mousemove, więc koszt jest stały niezależnie od tego, jak ciężka jest strona
 * (patrz wcześniejsza optymalizacja /tasks2, gdzie magnetyczne przyciski robione
 * na wszystkich elementach potrafiły mulić długie tabele).
 */
(function initCursorGlow() {
    if (typeof window === 'undefined') return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    if (window.matchMedia('(pointer: coarse)').matches) return;

    let glow = null;
    let mouseX = 0;
    let mouseY = 0;
    let rafPending = false;

    const ensureGlow = () => {
        if (glow) return glow;
        glow = document.createElement('div');
        glow.className = 'cl-cursor-glow';
        document.body.appendChild(glow);
        return glow;
    };

    const update = () => {
        rafPending = false;
        const el = ensureGlow();
        el.style.opacity = '1';
        el.style.transform = `translate(${mouseX - 230}px, ${mouseY - 230}px)`;
    };

    document.addEventListener('mousemove', (e) => {
        mouseX = e.clientX;
        mouseY = e.clientY;
        if (!rafPending) {
            rafPending = true;
            requestAnimationFrame(update);
        }
    }, { passive: true });

    document.addEventListener('mouseleave', () => {
        if (glow) glow.style.opacity = '0';
    });
})();

/**
 * Livewire po morphowaniu DOM potrafi „zsunąć” stronę w dół (focus / przeliczenie layoutu).
 * Przywracamy scroll okna po udanym commicie tylko dla komponentów oznaczonych
 * `data-livewire-preserve-scroll` (np. planery wyjazdu — długie formularze).
 * Dodatkowo `morph.updated` — morph bywa kończony po callbacku `succeed`.
 */
document.addEventListener('livewire:init', () => {
    let preserveScrollPending = null;

    const restorePreserveScroll = () => {
        if (!preserveScrollPending) {
            return;
        }
        const { x, y } = preserveScrollPending;
        window.scrollTo(x, y);
    };

    Livewire.hook('commit', ({ component, succeed }) => {
        if (!component?.el?.closest?.('[data-livewire-preserve-scroll]')) {
            return;
        }
        const pos = { x: window.scrollX, y: window.scrollY };
        preserveScrollPending = pos;
        succeed(() => {
            const run = () => {
                restorePreserveScroll();
                queueMicrotask(restorePreserveScroll);
                requestAnimationFrame(() => {
                    restorePreserveScroll();
                    requestAnimationFrame(restorePreserveScroll);
                });
                setTimeout(restorePreserveScroll, 0);
            };
            run();
        });
    });

    Livewire.hook('morph.updated', ({ component }) => {
        if (!component?.el?.closest?.('[data-livewire-preserve-scroll]')) {
            return;
        }
        restorePreserveScroll();
        requestAnimationFrame(restorePreserveScroll);
    });
});

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

    /** Panel „przed/po” na /audit-logs — musi być w alpine:init (przed Alpine.start), nie w @stack po Livewire. */
    window.Alpine.data('auditDiffPanel', () => ({
        openState: false,
        payload: null,
        showDiff(detail) {
            this.payload = detail;
            this.openState = true;
        },
        close() {
            this.openState = false;
            this.payload = null;
        },
    }));
});

/**
 * Potwierdzenie przy wyborze auta z przeterminowanym OC lub przeglądem.
 * @param {{ oc?: boolean, przeglad?: boolean }} flags
 * @returns {boolean} true = można kontynuować
 */
window.confirmVehicleDocumentsIfNeeded = function (flags) {
    if (!flags || (!flags.oc && !flags.przeglad)) {
        return true;
    }
    const parts = [];
    if (flags.oc) {
        parts.push('nieważne OC');
    }
    if (flags.przeglad) {
        parts.push('nieważny przegląd');
    }
    const msg =
        'Uwaga: wybrane auto ma ' +
        parts.join(' oraz ') +
        '. Czy na pewno chcesz kontynuować?';
    return window.confirm(msg);
};

/**
 * Podpięcie pod formularz (select name=vehicle_id): przed submit — confirm.
 */
window.attachVehicleDocumentConfirmToForm = function (formEl, selectName, payload) {
    if (!formEl || !payload) {
        return;
    }
    const sel = formEl.querySelector(`select[name="${selectName || 'vehicle_id'}"]`);
    if (!sel) {
        return;
    }
    formEl.addEventListener(
        'submit',
        function (e) {
            if (formEl.dataset.vehicleDocConfirmOk === '1') {
                delete formEl.dataset.vehicleDocConfirmOk;
                return;
            }
            const id = sel.value;
            if (!id || !payload[id]) {
                return;
            }
            const row = payload[id];
            if (!row.oc && !row.przeglad) {
                return;
            }
            if (!window.confirmVehicleDocumentsIfNeeded(row)) {
                e.preventDefault();
                e.stopPropagation();
                return;
            }
            formEl.dataset.vehicleDocConfirmOk = '1';
        },
        true
    );
};

/**
 * Ekran inicjalizacji systemu (x-boot-screen).
 *
 * Pokazuje się przy wysyłce formularza oznaczonego data-boot-screen (logowanie)
 * i zostaje na ekranie aż przeglądarka przejdzie na kolejną stronę.
 */
(function () {
    const boot = document.getElementById('clBootScreen');

    if (!boot) {
        return;
    }

    /**
     * @param {boolean} auto Odegraj pełną sekwencję i zgaś ekran samym CSS-em.
     */
    const show = (auto = false) => {
        boot.classList.remove('cl-boot--on', 'cl-boot--auto');
        void boot.offsetWidth; // wymuszony reflow — inaczej animacja nie ruszy od nowa
        boot.classList.add('cl-boot--on');

        if (auto) {
            boot.classList.add('cl-boot--auto');
        }

        boot.setAttribute('aria-hidden', 'false');
    };

    const hide = () => {
        boot.classList.remove('cl-boot--on', 'cl-boot--auto');
        boot.setAttribute('aria-hidden', 'true');
    };

    document.querySelectorAll('form[data-boot-screen]').forEach((form) => {
        form.addEventListener('submit', () => {
            // Formularz z błędem walidacji nie wychodzi — nie zasłaniaj go.
            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                return;
            }
            show();
        });
    });

    // Powrót „wstecz” z bfcache pokazałby zamrożony ekran startowy.
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            hide();
        }
    });

    window.clShowBootScreen = show;
    window.clHideBootScreen = hide;
})();
