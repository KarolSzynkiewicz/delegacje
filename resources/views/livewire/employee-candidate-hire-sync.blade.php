<div>
    <button type="button" wire:click="openModal"
            class="btn btn-primary"
            title="Porównaj pracowników z kandydatami po telefonie">
        <i class="bi bi-people me-1"></i>Podgląd i synchronizacja
    </button>

    @if($show)
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.6);" wire:click.self="closeModal">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content" style="background:var(--bg-card,#1e2535);border:1px solid var(--glass-border,rgba(255,255,255,.1));color:var(--text-main,#f1f5f9);">

                <div class="modal-header" style="border-color:var(--glass-border);">
                    <h5 class="modal-title">
                        <i class="bi bi-people me-2" style="color:#34d399"></i>
                        Synchronizacja: pracownicy → kandydaci (zatrudnieni)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeModal"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-info d-flex gap-2 align-items-start mb-3" style="font-size:.85rem;background:rgba(37,99,235,.15);border-color:rgba(37,99,235,.3);color:#93c5fd;">
                        <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                        <div>
                            Porównuje pracowników z kandydatami po <strong>znormalizowanym numerze telefonu</strong>
                            (ten sam normalizer co w module rekrutacji).<br>
                            <strong>Brak w bazie kandydatów</strong> → utworzy kandydata + lead + proces w statusie <em>Zatrudniony</em> (z linkiem do pracownika).<br>
                            <strong>Kandydat niezatrudniony</strong> → doda nowy proces <em>Zatrudniony</em> (bez nadpisywania danych kandydata).<br>
                            <strong>Już zatrudniony / bez telefonu</strong> → pominięte.
                        </div>
                    </div>

                    <div wire:loading.flex wire:target="openModal,loadPreview" class="align-items-center gap-2 mb-3" style="font-size:.85rem;color:var(--text-muted);">
                        <i class="bi bi-hourglass-split"></i> Buduję podgląd…
                    </div>

                    @if($error)
                        <div class="alert alert-danger d-flex gap-2 align-items-start" style="font-size:.85rem;">
                            <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                            {{ $error }}
                        </div>
                    @endif

                    @if($applyResult)
                        <div class="alert alert-success d-flex gap-2 align-items-start" style="font-size:.9rem;">
                            <i class="bi bi-check-circle-fill flex-shrink-0 mt-1"></i>
                            <div>
                                Synchronizacja zakończona.<br>
                                <strong>{{ $applyResult['created'] }}</strong> nowych kandydatów
                                &nbsp;·&nbsp;
                                <strong>{{ $applyResult['marked'] }}</strong> oznaczonych jako zatrudnionych
                                &nbsp;·&nbsp;
                                <strong>{{ $applyResult['skipped'] }}</strong> pominiętych
                            </div>
                        </div>
                        <button type="button" wire:click="loadPreview" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="bi bi-arrow-repeat me-1"></i>Odśwież podgląd
                        </button>
                        <button type="button" wire:click="closeModal" class="btn btn-sm btn-primary">
                            Zamknij
                        </button>
                    @endif

                    @if(!empty($preview))
                        @php
                            $previewCol = collect($preview);
                            $totalRows = $previewCol->count();
                            $missingCount = $previewCol->where('status', 'missing')->count();
                            $unhiredCount = $previewCol->where('status', 'unhired')->count();
                            $hiredCount = $previewCol->where('status', 'hired')->count();
                            $noPhoneCount = $previewCol->where('status', 'no_phone')->count();
                        @endphp

                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span style="font-size:.8rem;color:var(--text-muted);">
                                Podgląd: <strong>{{ $totalRows }}</strong> pracowników
                                &nbsp;·&nbsp;
                                <span style="color:#34d399">{{ $missingCount }} brak w bazie kandydatów</span>
                                &nbsp;·&nbsp;
                                <span style="color:#fbbf24">{{ $unhiredCount }} kandydat (niezatrudniony)</span>
                                &nbsp;·&nbsp;
                                <span style="color:#a78bfa">{{ $hiredCount }} już zatrudniony</span>
                                @if($noPhoneCount > 0)
                                    &nbsp;·&nbsp;
                                    <span style="color:#f87171">{{ $noPhoneCount }} bez telefonu</span>
                                @endif
                            </span>
                        </div>

                        <div class="table-responsive" style="max-height:380px;overflow-y:auto;">
                            <table class="table table-sm mb-0" style="font-size:.8rem;border-collapse:collapse;">
                                <thead style="position:sticky;top:0;z-index:1;background:var(--bg-card,#1e2535);">
                                    <tr style="color:var(--text-muted);font-size:.68rem;text-transform:uppercase;letter-spacing:.05em;">
                                        <th style="padding:.4rem .6rem;border-bottom:1px solid var(--glass-border);">#</th>
                                        <th style="padding:.4rem .6rem;border-bottom:1px solid var(--glass-border);">Pracownik</th>
                                        <th style="padding:.4rem .6rem;border-bottom:1px solid var(--glass-border);">Telefon</th>
                                        <th style="padding:.4rem .6rem;border-bottom:1px solid var(--glass-border);">Kandydat</th>
                                        <th style="padding:.4rem .6rem;border-bottom:1px solid var(--glass-border);">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($preview as $i => $row)
                                        @php
                                            $status = $row['status'] ?? '';
                                            $dimRow = in_array($status, ['hired', 'no_phone'], true);
                                        @endphp
                                        <tr style="border-bottom:1px solid var(--glass-border);{{ $dimRow ? 'opacity:.45;' : '' }}">
                                            <td style="padding:.35rem .6rem;color:var(--text-muted);">{{ $i + 1 }}</td>
                                            <td style="padding:.35rem .6rem;">
                                                {{ trim(($row['first_name'] ?? '').' '.($row['last_name'] ?? '')) ?: '—' }}
                                            </td>
                                            <td style="padding:.35rem .6rem;">
                                                @if($row['phone'] ?? null)
                                                    {{ $row['phone'] }}
                                                @else
                                                    <span style="color:#f87171;">{{ ($row['phone_raw'] ?? '') ?: '—' }}</span>
                                                @endif
                                            </td>
                                            <td style="padding:.35rem .6rem;color:var(--text-muted);">
                                                {{ ($row['candidate_name'] ?? '') ?: '—' }}
                                            </td>
                                            <td style="padding:.35rem .6rem;">
                                                @switch($status)
                                                    @case('missing')
                                                        <span class="badge" style="background:rgba(52,211,153,.15);color:#34d399;font-size:.62rem;">Brak w bazie kandydatów</span>
                                                        @break
                                                    @case('unhired')
                                                        <span class="badge" style="background:rgba(251,191,36,.15);color:#fbbf24;font-size:.62rem;">Kandydat (niezatrudniony)</span>
                                                        @break
                                                    @case('hired')
                                                        <span class="badge" style="background:rgba(167,139,250,.15);color:#a78bfa;font-size:.62rem;">Kandydat zatrudniony</span>
                                                        @break
                                                    @default
                                                        <span class="badge" style="background:rgba(239,68,68,.2);color:#f87171;font-size:.62rem;">Brak telefonu</span>
                                                @endswitch
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="modal-footer" style="border-color:var(--glass-border);">
                    @if(!empty($preview))
                        @php $toApply = collect($preview)->where('actionable', true)->count(); @endphp
                        <button type="button" wire:click="doApply"
                                wire:loading.attr="disabled"
                                @disabled($toApply === 0)
                                class="btn btn-primary">
                            <span wire:loading.remove wire:target="doApply">
                                <i class="bi bi-check-lg me-1"></i>Synchronizuj {{ $toApply }}
                            </span>
                            <span wire:loading wire:target="doApply">
                                <i class="bi bi-hourglass-split me-1"></i>Synchronizuję…
                            </span>
                        </button>
                    @endif
                    <button type="button" wire:click="closeModal" class="btn btn-outline-secondary">
                        Anuluj
                    </button>
                </div>

            </div>
        </div>
    </div>
    @endif
</div>
