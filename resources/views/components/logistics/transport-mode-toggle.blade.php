@php
    $missing = $required && empty($mode);
@endphp

<div {{ $attributes->merge(['class' => 'rounded-3 p-2 transition-all d-flex flex-column w-100 logistics-trip-header-card']) }}
     style="min-height: 106px; justify-content: space-between; {{ $missing ? 'border: 1px solid rgba(239,68,68,0.65) !important; background: rgba(239,68,68,0.12) !important; box-shadow: 0 0 0 1px rgba(239,68,68,0.15);' : '' }}">
    <label class="form-label small mb-1 {{ $missing ? 'text-danger fw-semibold' : 'text-muted' }}">
        Czym <span class="text-danger">*</span>
    </label>
    <div class="d-flex gap-2 align-items-stretch logistics-trip-header-control-row">
        <button type="button"
                @if($interactive) wire:click="requestSetTransportMode('public')" @endif
                class="btn btn-sm flex-fill d-inline-flex align-items-center justify-content-center logistics-trip-header-control {{ $mode === 'public' ? 'btn-primary' : 'btn-outline-secondary' }}"
                style="min-height: 2.125rem;"
                @if(! $interactive) title="Podgląd statyczny (katalog komponentów)" @endif>
            <i class="bi bi-airplane me-1"></i> Publiczny
        </button>
        <button type="button"
                @if($interactive) wire:click="requestSetTransportMode('own')" @endif
                class="btn btn-sm flex-fill d-inline-flex align-items-center justify-content-center logistics-trip-header-control {{ $mode === 'own' ? 'btn-success' : 'btn-outline-secondary' }}"
                style="min-height: 2.125rem;"
                @if(! $interactive) title="Podgląd statyczny (katalog komponentów)" @endif>
            <i class="bi bi-car-front me-1"></i> Własny
        </button>
    </div>

    <div class="small mt-1 mb-0 flex-shrink-0 {{ $missing ? 'text-danger' : 'text-muted' }}"
         style="font-size: 0.72rem; min-height: 16px; {{ $missing ? '' : 'visibility:hidden;' }}">
        <i class="bi bi-exclamation-circle me-1"></i>Wybierz sposób transportu.
    </div>
</div>

