{{-- Wymaga nadrzędnego x-data="{ open: false }" — otwierany przyciskiem Narzędzia --}}
<template x-teleport="body">
    <div x-show="open"
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="rp-tools-shell"
         @keydown.escape.window="open = false">
        <div class="rp-modal-backdrop" @click="open = false"></div>
        <div class="rp-modal-wrap" style="z-index:999996;pointer-events:none;">
            <div class="rp-tools-modal" @click.stop style="pointer-events:auto;">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                    <div>
                        <h5 class="mb-1" style="font-size:1.05rem;font-weight:700;">Narzędzia</h5>
                        <p class="mb-0" style="font-size:.82rem;color:var(--text-muted);">
                            Akcje działające na całej liście kandydatów
                        </p>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0"
                            style="padding:4px 9px;line-height:1;" @click="open = false" aria-label="Zamknij">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <button type="button"
                        @click="open = false; $dispatch('open-mbs-import')"
                        class="rp-tool-item mb-2">
                    <span class="rp-tool-item__icon rp-tool-item__icon--blue">
                        <i class="bi bi-cloud-upload"></i>
                    </span>
                    <span class="flex-grow-1 min-width-0">
                        <span class="d-block fw-semibold" style="font-size:.9rem;">Import MBS</span>
                        <span style="font-size:.78rem;color:var(--text-muted);">
                            Wciągnij nowe zgłoszenia z Meta Business Suite
                        </span>
                    </span>
                    <i class="bi bi-chevron-right flex-shrink-0" style="color:var(--text-muted);font-size:.85rem;"></i>
                </button>

                <button type="button"
                        @click="open = false; $dispatch('open-workload-modal')"
                        class="rp-tool-item mb-2">
                    <span class="rp-tool-item__icon rp-tool-item__icon--green">
                        <i class="bi bi-people"></i>
                    </span>
                    <span class="flex-grow-1 min-width-0">
                        <span class="d-block fw-semibold" style="font-size:.9rem;">Podziel pracę</span>
                        <span style="font-size:.78rem;color:var(--text-muted);">
                            Rozdziel kandydatów z listy pomiędzy rekruterów
                        </span>
                    </span>
                    <i class="bi bi-chevron-right flex-shrink-0" style="color:var(--text-muted);font-size:.85rem;"></i>
                </button>

                <a href="{{ route('recruitment-analytics.index') }}"
                   class="rp-tool-item mb-2 text-decoration-none"
                   @click="open = false">
                    <span class="rp-tool-item__icon rp-tool-item__icon--purple">
                        <i class="bi bi-graph-up-arrow"></i>
                    </span>
                    <span class="flex-grow-1 min-width-0">
                        <span class="d-block fw-semibold" style="font-size:.9rem;">Analityka</span>
                        <span style="font-size:.78rem;color:var(--text-muted);">
                            Lejek, czasy reakcji i aktywność rekruterów
                        </span>
                    </span>
                    <i class="bi bi-chevron-right flex-shrink-0" style="color:var(--text-muted);font-size:.85rem;"></i>
                </a>

                <button type="button"
                        @click="showViews = !showViews"
                        class="rp-tool-item mb-2"
                        :class="showViews ? 'is-expanded' : ''">
                    <span class="rp-tool-item__icon rp-tool-item__icon--amber">
                        <i class="bi bi-bookmark"></i>
                    </span>
                    <span class="flex-grow-1 min-width-0">
                        <span class="d-block fw-semibold" style="font-size:.9rem;">Widoki</span>
                        <span style="font-size:.78rem;color:var(--text-muted);">
                            Zapisz i zarządzaj widokami filtrów
                            @if($savedViews->isNotEmpty())
                                · {{ $savedViews->count() }}
                            @endif
                        </span>
                    </span>
                    <i class="bi flex-shrink-0" style="color:var(--text-muted);font-size:.85rem;"
                       :class="showViews ? 'bi-chevron-down' : 'bi-chevron-right'"></i>
                </button>

                <div x-show="showViews" x-cloak class="rp-tools-views-panel mb-2">
                    @if($view && $activeViewName)
                        <div class="mb-2 p-2 rounded" style="background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2)">
                            <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:4px">Aktywny widok</div>
                            <div class="fw-semibold small mb-2">{{ $activeViewName }}</div>
                            <button type="button" wire:click="clearView" @click="open=false"
                                    class="btn btn-sm btn-outline-secondary w-100">Widok domyślny</button>
                        </div>
                    @endif

                    @if($savedViews->isNotEmpty())
                        <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:6px">Zapisane</div>
                        <div class="mb-2" style="max-height:140px;overflow-y:auto;">
                            @foreach($savedViews as $savedView)
                                <div class="d-flex align-items-center gap-1 mb-1">
                                    <button type="button" wire:click="loadView('{{ $savedView->slug }}')" @click="open=false"
                                            class="btn btn-sm btn-link text-start flex-grow-1 p-1 text-decoration-none {{ $view === $savedView->slug ? 'fw-bold' : '' }}"
                                            style="font-size:.82rem;color:var(--text-main);">
                                        <i class="bi bi-bookmark{{ $view === $savedView->slug ? '-fill' : '' }} me-1"
                                           style="color:var(--primary);font-size:.75rem"></i>{{ $savedView->name }}
                                    </button>
                                    <button type="button" wire:click="deleteView('{{ $savedView->slug }}')"
                                            class="btn btn-sm btn-link p-1 flex-shrink-0"
                                            style="color:var(--danger);" title="Usuń">
                                        <i class="bi bi-trash" style="font-size:.78rem"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:6px">
                        {{ $view ? 'Nadpisz aktywny widok' : 'Zapisz bieżący widok' }}
                    </div>
                    <div class="d-flex gap-2">
                        <input wire:model="saveViewName" type="text" class="form-control form-control-sm flex-grow-1"
                               placeholder="Nazwa widoku…" wire:keydown.enter="saveView" @click.stop>
                        <button type="button" wire:click="saveView" @click="open=false"
                                class="btn btn-sm btn-primary flex-shrink-0" title="Zapisz">
                            <i class="bi bi-floppy"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
