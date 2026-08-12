{{-- Krok 4 (transport publiczny): tylko odcinek lotu/przesiadki.
     Transfery ziemne (na/z lotniska) robimy osobno w kreatorze transferów. --}}
<div class="rtp-card rounded-3 p-3 mb-3" style="background: var(--bg-card); border: 1px solid rgba(59,130,246,0.28);" wire:key="step4-public-card-flight">
    <div class="d-flex align-items-center gap-2 mb-2">
        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
             style="width: 32px; height: 32px; font-size: 0.85rem; background: rgba(59,130,246,0.35); flex-shrink: 0;">1</div>
        <div>
            <h6 class="mb-0 fw-semibold" style="font-size: 0.92rem;">
                @if(($publicTransportHubKind ?? null) === 'station')
                    Przesiadki (dworzec)
                @else
                    Lot
                @endif
            </h6>
            <div class="text-muted" style="font-size: 0.72rem;">Jak ustawiłeś w nagłówku wyjazdu</div>
        </div>
    </div>
    @if(!empty($startAirportData) && !empty($endAirportData))
        <div class="fw-semibold mb-1" style="font-size: 1rem;">
            @if(($publicTransportHubKind ?? null) === 'station')
                <i class="bi bi-train-front text-primary me-1"></i>
            @else
                <i class="bi bi-airplane text-primary me-1"></i>
            @endif
            {{ $startAirportData['name'] }} → {{ $endAirportData['name'] }}
        </div>
        <div class="small text-muted" style="font-size: 0.78rem;">Bilety na ten odcinek: sekcja w nagłówku (powyżej kroku 4).</div>
    @else
        <div class="small text-warning">Wybierz punkt startowy i docelowy we wcześniejszym kroku.</div>
    @endif
</div>

<div class="rounded-3 p-3 mb-0" style="background: rgba(148,163,184,0.06); border: 1px dashed rgba(148,163,184,0.28);">
    <div class="d-flex align-items-start gap-2">
        <i class="bi bi-info-circle text-info mt-1" style="font-size: 0.95rem;"></i>
        <div>
            <div class="fw-semibold small mb-1">Transfery na/z lotniska — osobno</div>
            <p class="small text-muted mb-2 mb-md-3" style="font-size: 0.78rem;">
                Dojazdu firmą na lotnisko startowe ani transferu z lotniska docelowego nie planujesz już w wyjeździe.
                Dodaj je w kreatorze transferów i powiąż z wyjazdem, gdy potrzeba.
            </p>
            <a href="{{ route('transfers.create') }}" class="btn btn-sm btn-outline-info" target="_blank" rel="noopener">
                <i class="bi bi-box-arrow-up-right me-1"></i> Otwórz kreator transferów
            </a>
        </div>
    </div>
</div>
