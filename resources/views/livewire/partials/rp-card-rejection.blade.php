{{-- Etap odrzucenia: jedyne, co się liczy, to powód --}}
<div class="rp-doc-section rp-doc-section--rejection">
    <div class="rp-field-label">
        <i class="bi bi-x-octagon me-1"></i>Odrzucenie
    </div>
    @if($selected->rejection_reason)
        <div class="rp-rejection-callout">
            <i class="bi bi-x-octagon-fill"></i>
            <div>
                <div class="rp-rejection-callout__label">Powód odrzucenia</div>
                <div class="rp-rejection-callout__reason">{{ $selected->rejection_reason->label() }}</div>
                @if($selected->rejection_reason_note)
                    <div class="rp-rejection-callout__note">{{ $selected->rejection_reason_note }}</div>
                @endif
            </div>
        </div>
    @else
        <p style="color:var(--text-muted);font-size:.85rem;margin:0;">
            Nie zapisano powodu odrzucenia — pojawi się tu, gdy odrzucisz proces z poziomu paska etapów.
        </p>
    @endif
</div>
