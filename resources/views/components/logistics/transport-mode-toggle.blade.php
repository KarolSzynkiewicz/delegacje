@php
    $missing = $required && empty($mode);
@endphp

<div {{ $attributes->merge(['class' => 'rounded-3 p-2 transition-all d-flex flex-column w-100 h-100 logistics-trip-header-card' . ($missing ? ' logistics-trip-header-card--invalid' : '')]) }}
     style="justify-content: space-between;">
    <label class="form-label small mb-1 {{ $missing ? 'text-danger fw-semibold' : 'text-muted' }}">
        Czym <span class="text-danger">*</span>
    </label>
    <div class="d-flex gap-2 align-items-stretch logistics-trip-header-control-row" style="overflow: hidden;">
        <button type="button"
                @if($interactive)
                    wire:click="requestPublicTransportModeButtonAction"
                    title="{{ $publicButtonTitle }}"
                @endif
                class="btn flex-fill d-inline-flex align-items-center justify-content-center logistics-trip-header-control min-w-0 {{ $mode === 'public' ? 'btn-primary' : 'btn-outline-secondary' }}"
                style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                @if(! $interactive) title="Podgląd statyczny (katalog komponentów)" @endif>
            <i class="bi {{ $publicButtonIcon }} me-1 flex-shrink-0"></i><span class="text-truncate">{{ $publicButtonLabel }}</span>
        </button>
        <button type="button"
                @if($interactive) wire:click="requestSetTransportMode('own')" @endif
                class="btn flex-fill d-inline-flex align-items-center justify-content-center logistics-trip-header-control min-w-0 {{ $mode === 'own' ? 'btn-success' : 'btn-outline-secondary' }}"
                style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                @if(! $interactive) title="Podgląd statyczny (katalog komponentów)" @endif>
            <i class="bi bi-car-front me-1 flex-shrink-0"></i><span class="text-truncate">Własny</span>
        </button>
    </div>

    <div class="small mt-1 mb-0 flex-shrink-0 logistics-trip-header-hint {{ $missing ? 'text-danger' : 'text-muted' }}"
         style="min-height: 16px; {{ $missing ? '' : 'visibility:hidden;' }}">
        <i class="bi bi-exclamation-circle me-1"></i>Wybierz sposób transportu.
    </div>
</div>
