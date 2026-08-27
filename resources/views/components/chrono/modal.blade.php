@props([
    'key',                       // unikalny prefiks wire:key (jeden modal = jeden ekran)
    'close',                     // metoda Livewire zamykająca okno
    'fetch' => null,             // metoda Livewire odpalana po wyrenderowaniu okna
    'loading' => false,
    'error' => null,
    'ready' => false,            // model zwrócił propozycje do zatwierdzenia
    'title' => 'AskChrono',
    'lead' => null,              // zdanie nad listą propozycji
    'thinking' => 'Chrono czyta kontekst i układa propozycję.',
    'statusIdle' => 'Gotowy do pracy',
    'statusLoading' => 'Pracuję nad propozycją…',
    'statusError' => 'Nie udało się przygotować propozycji',
    'statusReady' => 'Sprawdź i zatwierdź',
    'emptyMessage' => 'Brak propozycji do wyświetlenia.',
    'dialogClass' => 'modal-lg modal-dialog-scrollable',
    'chrome' => true,            // false = sam body (Assist ma własny header/stopkę)
    'componentId' => null,       // Livewire id po @teleport (zagnieżdżony komponent: $wire to rodzic)
])

@php
    $botState = $loading ? 'thinking' : ($ready ? 'done' : 'idle');
    $closeCall = $componentId
        ? 'Livewire.find('.json_encode((string) $componentId).').call('.json_encode((string) $close).')'
        : null;
    $closeBackdrop = $closeCall
        ? 'if (event.target === this) { '.$closeCall.'; }'
        : null;

    $status = match (true) {
        (bool) $loading => $statusLoading,
        (bool) $error => $statusError,
        (bool) $ready => $statusReady,
        default => $statusIdle,
    };
@endphp

@teleport('body')
<div
    class="modal fade show d-block"
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    style="background:rgba(0,0,0,.75);z-index:2000;"
    @if($closeBackdrop)
        onclick="{{ $closeBackdrop }}"
    @else
        wire:click.self="{{ $close }}"
    @endif
    wire:key="{{ $key }}-modal"
>
    <div class="modal-dialog {{ $dialogClass }}">
        <div class="modal-content" style="background:var(--bg-card,#1e2535);border:1px solid var(--glass-border,rgba(255,255,255,.1));color:var(--text-main,#f1f5f9);">
            @if($chrome)
                <div class="modal-header" style="border-color:var(--glass-border);">
                    <div class="d-flex align-items-center gap-3">
                        <x-ask-chrono-bot :size="54" :state="$botState" />
                        <div>
                            <h5 class="modal-title ac-modal__title mb-0">{{ $title }}</h5>
                            <span class="ac-modal__status">{{ $status }}</span>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        @if($closeCall)
                            onclick="{{ $closeCall }}"
                        @else
                            wire:click="{{ $close }}"
                        @endif
                    ></button>
                </div>
            @endif

            <div class="modal-body {{ $chrome ? '' : 'p-2 p-md-3' }}">
                @if($loading)
                    <div
                        class="ac-thinking"
                        wire:key="{{ $key }}-thinking"
                        @if($fetch) x-data="{}" x-init="$wire.{{ $fetch }}()" @endif
                    >
                        <div class="ac-thinking__bars">
                            <span></span><span></span><span></span>
                        </div>
                        <p class="ac-thinking__text mb-0">{{ $thinking }}</p>
                    </div>
                @endif

                @if(! $loading && $lead)
                    <p class="text-muted small mb-3">{{ $lead }}</p>
                @endif

                @if($error)
                    <x-ui.alert variant="danger" class="mb-3">{{ $error }}</x-ui.alert>
                @endif

                @if(! $loading && $ready)
                    {{ $slot }}
                @elseif(! $loading && ! $error)
                    <x-ui.empty-state icon="robot" :message="$emptyMessage" />
                @endif
            </div>

            @if($chrome)
                <div class="modal-footer" style="border-color:var(--glass-border);">
                    @if(! $loading && $ready && isset($footer))
                        {{ $footer }}
                    @else
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            @if($closeCall)
                                onclick="{{ $closeCall }}"
                            @else
                                wire:click="{{ $close }}"
                            @endif
                        >
                            Zamknij
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
@endteleport
