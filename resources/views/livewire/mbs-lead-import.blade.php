<div>
    {{-- ══════════ TRIGGER BUTTON ══════════ --}}
    <button type="button" wire:click="openModal"
            class="btn btn-sm btn-outline-secondary"
            title="Importuj leady z Meta Business Suite">
        <i class="bi bi-cloud-upload me-1"></i>Import MBS
    </button>

    {{-- ══════════ MODAL ══════════ --}}
    @if($show)
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.6);" wire:click.self="closeModal">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content" style="background:var(--bg-card,#1e2535);border:1px solid var(--glass-border,rgba(255,255,255,.1));color:var(--text-main,#f1f5f9);">

                {{-- Header --}}
                <div class="modal-header" style="border-color:var(--glass-border);">
                    <h5 class="modal-title">
                        <i class="bi bi-meta me-2" style="color:#1877f2"></i>
                        Import leadów — Meta Business Suite / Centrum kontaktu
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeModal"></button>
                </div>

                {{-- Body --}}
                <div class="modal-body">

                    {{-- Info --}}
                    <div class="alert alert-info d-flex gap-2 align-items-start mb-3" style="font-size:.85rem;background:rgba(37,99,235,.15);border-color:rgba(37,99,235,.3);color:#93c5fd;">
                        <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                        <div>
                            Importuj plik CSV pobrany z <strong>Meta Business Suite → Centrum kontaktu → Eksport leadów</strong>.<br>
                            Źródło leadów zostanie ustawione na <strong>Meta Business Suite (FB/IG)</strong>.<br>
                            Istniejący kandydat (ten sam telefon) otrzyma nowy lead (<em>ponowienie</em>) — jego dane <strong>nie zostaną nadpisane</strong>.<br>
                            Wiersze z tym samym numerem telefonu <strong>i</strong> tą samą datą leada co już istniejący wpis zostaną pominięte jako <em>już wpisane</em> (zabezpieczenie przed dublikacją przy ponownym imporcie tego samego pliku).
                        </div>
                    </div>

                    {{-- File picker --}}
                    @if(! $preview && ! $importResult)
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.85rem;color:var(--text-muted);">Plik CSV z MBS</label>
                        <input type="file"
                               wire:model="csvFile"
                               accept=".csv,.txt"
                               class="form-control @error('csvFile') is-invalid @enderror"
                               style="background:rgba(255,255,255,.06);border-color:var(--glass-border);color:var(--text-main);">
                        @error('csvFile')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div wire:loading wire:target="csvFile" class="mt-2 small" style="color:var(--text-muted);">
                            <i class="bi bi-hourglass-split me-1"></i>Wczytuję plik…
                        </div>
                    </div>
                    @endif

                    {{-- Parse error --}}
                    @if($parseError)
                        <div class="alert alert-danger d-flex gap-2 align-items-start" style="font-size:.85rem;">
                            <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                            {{ $parseError }}
                        </div>
                        <button type="button" wire:click="openModal" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Wybierz inny plik
                        </button>
                    @endif

                    {{-- Import success --}}
                    @if($importResult)
                        <div class="alert alert-success d-flex gap-2 align-items-start" style="font-size:.9rem;">
                            <i class="bi bi-check-circle-fill flex-shrink-0 mt-1"></i>
                            <div>
                                Import zakończony.<br>
                                <strong>{{ $importResult['imported'] }}</strong> leadów zaimportowanych
                                @if($importResult['duplicates'] > 0)
                                    &nbsp;·&nbsp; <strong>{{ $importResult['duplicates'] }}</strong> pominięto (już wpisane)
                                @endif
                                @if($importResult['skipped'] > 0)
                                    &nbsp;·&nbsp; <strong>{{ $importResult['skipped'] }}</strong> pominięto (brak telefonu)
                                @endif
                            </div>
                        </div>
                        <button type="button" wire:click="openModal" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="bi bi-cloud-upload me-1"></i>Importuj kolejny plik
                        </button>
                        <button type="button" wire:click="closeModal" class="btn btn-sm btn-primary">
                            Zamknij
                        </button>
                    @endif

                    {{-- Preview table --}}
                    @if(!empty($preview))
                        @php
                            $previewCol      = collect($preview);
                            $totalRows       = $previewCol->count();
                            $newCount        = $previewCol->where('exists', false)->whereNotNull('phone')->count();
                            $resubmitCount   = $previewCol->where('exists', true)->where('duplicate', false)->count();
                            $duplicateCount  = $previewCol->where('duplicate', true)->count();
                            $noPhoneCount    = $previewCol->filter(fn($r) => !$r['phone'])->count();
                        @endphp

                        {{-- Detected columns strip --}}
                        @if(!empty($detectedHeaders))
                        <div class="d-flex flex-wrap gap-2 mb-3" style="font-size:.72rem;">
                            @foreach(['name' => 'Imię/Nazwisko', 'phone' => 'Telefon', 'email' => 'E-mail', 'created_at' => 'Data leada'] as $field => $label)
                                @php $col = $detectedHeaders[$field] ?? null; @endphp
                                <span style="padding:2px 8px;border-radius:20px;border:1px solid {{ $col ? 'rgba(52,211,153,.3)' : 'rgba(239,68,68,.3)' }};background:{{ $col ? 'rgba(52,211,153,.08)' : 'rgba(239,68,68,.08)' }};color:{{ $col ? '#6ee7b7' : '#fca5a5' }};">
                                    @if($col)
                                        <i class="bi bi-check-circle me-1"></i>{{ $label }}: <strong>{{ $col }}</strong>
                                    @else
                                        <i class="bi bi-exclamation-circle me-1"></i>{{ $label }}: nie wykryto
                                    @endif
                                </span>
                            @endforeach
                        </div>
                        @endif

                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span style="font-size:.8rem;color:var(--text-muted);">
                                Podgląd: <strong>{{ $totalRows }}</strong> wierszy
                                &nbsp;·&nbsp;
                                <span style="color:#34d399">{{ $newCount }} nowych kandydatów</span>
                                @if($resubmitCount > 0)
                                    &nbsp;·&nbsp;
                                    <span style="color:#60a5fa">{{ $resubmitCount }} ponowień</span>
                                @endif
                                @if($duplicateCount > 0)
                                    &nbsp;·&nbsp;
                                    <span style="color:#a78bfa">{{ $duplicateCount }} już wpisanych (pominięto)</span>
                                @endif
                                @if($noPhoneCount > 0)
                                    &nbsp;·&nbsp;
                                    <span style="color:#f87171">{{ $noPhoneCount }} bez telefonu (pominięto)</span>
                                @endif
                            </span>
                        </div>

                        <div class="table-responsive" style="max-height:380px;overflow-y:auto;">
                            <table class="table table-sm mb-0" style="font-size:.8rem;border-collapse:collapse;">
                                <thead style="position:sticky;top:0;z-index:1;background:var(--bg-card,#1e2535);">
                                    <tr style="color:var(--text-muted);font-size:.68rem;text-transform:uppercase;letter-spacing:.05em;">
                                        <th style="padding:.4rem .6rem;border-bottom:1px solid var(--glass-border);">#</th>
                                        <th style="padding:.4rem .6rem;border-bottom:1px solid var(--glass-border);">Imię i nazwisko</th>
                                        <th style="padding:.4rem .6rem;border-bottom:1px solid var(--glass-border);">Telefon</th>
                                        <th style="padding:.4rem .6rem;border-bottom:1px solid var(--glass-border);">E-mail</th>
                                        <th style="padding:.4rem .6rem;border-bottom:1px solid var(--glass-border);">Data leada</th>
                                        <th style="padding:.4rem .6rem;border-bottom:1px solid var(--glass-border);">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($preview as $i => $row)
                                        @php
                                            $hasPhone  = (bool)($row['phone'] ?? null);
                                            $isDup     = (bool)($row['duplicate'] ?? false);
                                            $isNew     = $hasPhone && ! ($row['exists'] ?? false);
                                            $dimRow    = ! $hasPhone || $isDup;
                                        @endphp
                                        <tr style="border-bottom:1px solid var(--glass-border);{{ $dimRow ? 'opacity:.45;' : '' }}">
                                            <td style="padding:.35rem .6rem;color:var(--text-muted);">{{ $i + 1 }}</td>
                                            <td style="padding:.35rem .6rem;">
                                                {{ trim(($row['first_name'] ?? '').' '.($row['last_name'] ?? '')) ?: '—' }}
                                            </td>
                                            <td style="padding:.35rem .6rem;">
                                                @if($hasPhone)
                                                    {{ $row['phone'] }}
                                                @else
                                                    <span style="color:#f87171;">{{ ($row['phone_raw'] ?? '') ?: '—' }}</span>
                                                @endif
                                            </td>
                                            <td style="padding:.35rem .6rem;color:var(--text-muted);">{{ ($row['email'] ?? '') ?: '—' }}</td>
                                            <td style="padding:.35rem .6rem;color:var(--text-muted);">{{ ($row['created_at'] ?? '') ?: '—' }}</td>
                                            <td style="padding:.35rem .6rem;">
                                                @if(! $hasPhone)
                                                    <span class="badge" style="background:rgba(239,68,68,.2);color:#f87171;font-size:.62rem;">Brak telefonu</span>
                                                @elseif($isDup)
                                                    <span class="badge" style="background:rgba(167,139,250,.15);color:#a78bfa;font-size:.62rem;">Już wpisane</span>
                                                @elseif($isNew)
                                                    <span class="badge" style="background:rgba(52,211,153,.15);color:#34d399;font-size:.62rem;">Nowy kandydat</span>
                                                @else
                                                    <span class="badge" style="background:rgba(96,165,250,.15);color:#60a5fa;font-size:.62rem;">Ponowienie</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                </div>{{-- /modal-body --}}

                {{-- Footer --}}
                <div class="modal-footer" style="border-color:var(--glass-border);">
                    @if(!empty($preview))
                        @php $toImport = collect($preview)->filter(fn($r) => (bool)($r['phone'] ?? null) && ! ($r['duplicate'] ?? false))->count(); @endphp
                        <button type="button" wire:click="doImport"
                                wire:loading.attr="disabled"
                                class="btn btn-primary">
                            <span wire:loading.remove wire:target="doImport">
                                <i class="bi bi-check-lg me-1"></i>Importuj {{ $toImport }} lead{{ $toImport === 1 ? '' : ($toImport < 5 ? 'y' : 'ów') }}
                            </span>
                            <span wire:loading wire:target="doImport">
                                <i class="bi bi-hourglass-split me-1"></i>Importuję…
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
