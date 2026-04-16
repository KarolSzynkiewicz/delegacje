@php
    $missing = $required && empty($mode);
@endphp

<div class="rounded-3 p-2 transition-all"
     style="{{ $missing ? 'border: 1px solid rgba(239,68,68,0.65) !important; background: rgba(239,68,68,0.12) !important; box-shadow: 0 0 0 1px rgba(239,68,68,0.15);' : '' }}">
    <label class="form-label small mb-1 {{ $missing ? 'text-danger fw-semibold' : 'text-muted' }}">
        Czym <span class="text-danger">*</span>
    </label>
    <div class="d-flex gap-2">
        <button type="button"
                wire:click="requestSetTransportMode('public')"
                class="btn btn-sm flex-fill {{ $mode === 'public' ? 'btn-primary' : 'btn-outline-secondary' }}">
            <i class="bi bi-airplane me-1"></i> Publiczny
        </button>
        <button type="button"
                wire:click="requestSetTransportMode('own')"
                class="btn btn-sm flex-fill {{ $mode === 'own' ? 'btn-success' : 'btn-outline-secondary' }}">
            <i class="bi bi-car-front me-1"></i> Własny
        </button>
    </div>

    @if($missing)
        <div class="small text-danger mt-1 mb-0" style="font-size: 0.72rem;">
            <i class="bi bi-exclamation-circle me-1"></i>Wybierz sposób transportu.
        </div>
    @endif
</div>

