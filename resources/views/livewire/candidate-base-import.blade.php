<div>
    {{-- ══════════ TRIGGER BUTTON ══════════ --}}
    <button type="button" wire:click="openModal"
            class="btn btn-sm btn-outline-secondary"
            title="Import pełnego profilu kandydata (CSV ze skryptu scalającego)">
        <i class="bi bi-person-lines-fill me-1"></i>Import bazy kandydatów
    </button>

    {{-- ══════════ MODAL ══════════ --}}
    @if($show)
    @teleport('body')
    <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true"
         style="background:rgba(0,0,0,.75);z-index:2000;"
         wire:click.self="closeModal"
         wire:key="candidate-base-import-modal">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content" style="background:var(--bg-card,#1e2535);border:1px solid var(--glass-border,rgba(255,255,255,.1));color:var(--text-main,#f1f5f9);">

                {{-- Header --}}
                <div class="modal-header" style="border-color:var(--glass-border);">
                    <h5 class="modal-title">
                        <i class="bi bi-person-lines-fill me-2" style="color:#34d399"></i>
                        Import pełnego profilu kandydata
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeModal"></button>
                </div>

                {{-- Body --}}
                <div class="modal-body">

                    {{-- Info --}}
                    <div class="alert alert-info d-flex gap-2 align-items-start mb-3" style="font-size:.85rem;background:rgba(37,99,235,.15);border-color:rgba(37,99,235,.3);color:#93c5fd;">
                        <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                        <div>
                            Narzędzie migracyjne do jednorazowego (lub powtarzalnego) importu <strong>pełnego profilu kandydata</strong>
                            — kandydat, lead, proces, notatki, kontakty — nie tylko samego leada jak import MBS.<br>
                            Oczekuje CSV o ustalonym schemacie (produkowanym przez skrypt scalający dane źródłowe, nie do ręcznego przygotowania).<br>
                            Istniejący kandydat (ten sam telefon) jest tylko <strong>wzbogacany</strong> — jego znane dane nigdy nie są nadpisywane.<br>
                            Jeśli kandydat ma już proces z leadem z tej samej daty (np. wpisany wcześniej przez codzienny import MBS),
                            zostanie <strong>wzbogacony</strong> zamiast tworzenia duplikatu — import jest bezpieczny do ponownego uruchomienia.
                        </div>
                    </div>

                    {{-- File picker --}}
                    @if(! $preview && ! $importing && (! $importResult || $parseError))
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.85rem;color:var(--text-muted);">Plik CSV (schemat scalonej bazy kandydatów)</label>
                        <input type="file"
                               wire:model="csvFile"
                               accept=".csv,.txt"
                               class="form-control @error('csvFile') is-invalid @enderror"
                               style="background:rgba(255,255,255,.06);border-color:var(--glass-border);color:var(--text-main);">
                        @error('csvFile')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div wire:loading wire:target="csvFile" class="mt-2 small" style="color:var(--text-muted);">
                            <i class="bi bi-hourglass-split me-1"></i>Wczytuję i analizuję plik…
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

                    {{-- Chunked import progress (one HTTP request ≈ one chunk — avoids Railway proxy 500) --}}
                    @if($importing)
                        @php
                            $pct = $importTotal > 0 ? min(100, (int) round(($importOffset / $importTotal) * 100)) : 0;
                        @endphp
                        <div class="alert alert-info d-flex gap-2 align-items-start mb-3" style="font-size:.9rem;background:rgba(37,99,235,.15);border-color:rgba(37,99,235,.3);color:#93c5fd;">
                            <i class="bi bi-hourglass-split flex-shrink-0 mt-1"></i>
                            <div class="w-100">
                                <div class="mb-2">
                                    Import w toku — zapisuję paczkami po {{ \App\Services\CandidateBaseImportService::IMPORT_CHUNK_SIZE }} wierszy
                                    (bez długiego pojedynczego requestu).<br>
                                    <strong>{{ $importOffset }}</strong> / <strong>{{ $importTotal }}</strong> wierszy
                                    @if($importResult)
                                        &nbsp;·&nbsp; {{ $importResult['created'] }} nowych
                                        &nbsp;·&nbsp; {{ $importResult['enriched'] }} wzbogaconych
                                    @endif
                                </div>
                                <div class="progress" style="height:8px;background:rgba(255,255,255,.08);">
                                    <div class="progress-bar" role="progressbar"
                                         style="width:{{ $pct }}%;background:#34d399;"
                                         aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Import success --}}
                    @if($importResult && ! $importing && empty($preview) && ! $parseError)
                        <div class="alert alert-success d-flex gap-2 align-items-start" style="font-size:.9rem;">
                            <i class="bi bi-check-circle-fill flex-shrink-0 mt-1"></i>
                            <div>
                                Import zakończony.<br>
                                <strong>{{ $importResult['created'] }}</strong> nowych kandydatów
                                &nbsp;·&nbsp;
                                <strong>{{ $importResult['enriched'] }}</strong> wzbogaconych
                                @if($importResult['skipped'] > 0)
                                    &nbsp;·&nbsp; <strong>{{ $importResult['skipped'] }}</strong> pominięto (brak telefonu)
                                @endif
                                @if(!empty($importResult['warnings']))
                                    &nbsp;·&nbsp; <strong>{{ count($importResult['warnings']) }}</strong> ostrzeżeń do ręcznej weryfikacji
                                @endif
                            </div>
                        </div>
                        @if(!empty($importResult['warnings']))
                            <div class="mb-3" style="font-size:.78rem;">
                                <div class="mb-1" style="color:var(--text-muted);">Ostrzeżenia (do ręcznej weryfikacji):</div>
                                <div class="table-responsive" style="max-height:220px;overflow-y:auto;border:1px solid var(--glass-border);border-radius:.375rem;">
                                    <ul class="mb-0 p-2" style="list-style:none;">
                                        @foreach($importResult['warnings'] as $w)
                                            <li class="py-1" style="border-bottom:1px solid var(--glass-border);color:#fbbf24;">
                                                <i class="bi bi-exclamation-triangle me-1"></i>{{ $w }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif
                        <button type="button" wire:click="openModal" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="bi bi-cloud-upload me-1"></i>Importuj kolejny plik
                        </button>
                        <button type="button" wire:click="closeModal" class="btn btn-sm btn-primary">
                            Zamknij
                        </button>
                    @endif

                    {{-- Preview table --}}
                    @if(!empty($preview) && ! $importing)
                        @php
                            $previewCol = collect($preview);
                            $totalRows = $previewCol->count();
                            $noPhoneCount = $previewCol->where('candidate_action', 'skip')->count();
                            $createCount = $previewCol->where('candidate_action', 'create')->count();
                            $enrichCount = $previewCol->where('candidate_action', 'enrich')->count();
                            $reuseCount = $previewCol->where('process_action', 'reuse')->count();
                            $warningRows = $previewCol->filter(fn ($r) => !empty($r['warnings']));
                            $statusCounts = $previewCol->filter(fn ($r) => ($r['candidate_action'] ?? null) !== 'skip')
                                ->countBy('resolved_status_label');
                            $displayLimit = 300;
                        @endphp

                        <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                            <span style="font-size:.8rem;color:var(--text-muted);">
                                Podgląd: <strong>{{ $totalRows }}</strong> wierszy
                                &nbsp;·&nbsp;
                                <span style="color:#34d399">{{ $createCount }} nowych kandydatów</span>
                                &nbsp;·&nbsp;
                                <span style="color:#60a5fa">{{ $enrichCount }} wzbogaconych</span>
                                @if($reuseCount > 0)
                                    &nbsp;·&nbsp;
                                    <span style="color:#a78bfa">{{ $reuseCount }} ponowne użycie istniejącego procesu</span>
                                @endif
                                @if($noPhoneCount > 0)
                                    &nbsp;·&nbsp;
                                    <span style="color:#f87171">{{ $noPhoneCount }} bez telefonu (pominięto)</span>
                                @endif
                                @if($warningRows->isNotEmpty())
                                    &nbsp;·&nbsp;
                                    <span style="color:#fbbf24">{{ $warningRows->count() }} z ostrzeżeniami</span>
                                @endif
                            </span>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-3" style="font-size:.72rem;">
                            @foreach($statusCounts as $label => $count)
                                <span style="padding:2px 8px;border-radius:20px;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.04);color:var(--text-muted);">
                                    {{ $label }}: <strong>{{ $count }}</strong>
                                </span>
                            @endforeach
                        </div>

                        @if($totalRows > $displayLimit)
                            <div class="alert alert-secondary py-2" style="font-size:.75rem;background:rgba(255,255,255,.05);border-color:var(--glass-border);color:var(--text-muted);">
                                <i class="bi bi-info-circle me-1"></i>
                                Wyświetlono pierwsze {{ $displayLimit }} z {{ $totalRows }} wierszy — import obejmie wszystkie.
                            </div>
                        @endif

                        <div class="table-responsive" style="max-height:calc(100vh - 420px);overflow-y:auto;">
                            <table class="table table-sm mb-0" style="font-size:.8rem;border-collapse:collapse;">
                                <thead style="position:sticky;top:0;z-index:1;background:var(--bg-card,#1e2535);">
                                    <tr style="color:var(--text-muted);font-size:.68rem;text-transform:uppercase;letter-spacing:.05em;">
                                        <th style="padding:.4rem .6rem;border-bottom:1px solid var(--glass-border);">#</th>
                                        <th style="padding:.4rem .6rem;border-bottom:1px solid var(--glass-border);">Imię i nazwisko</th>
                                        <th style="padding:.4rem .6rem;border-bottom:1px solid var(--glass-border);">Telefon</th>
                                        <th style="padding:.4rem .6rem;border-bottom:1px solid var(--glass-border);">Kandydat</th>
                                        <th style="padding:.4rem .6rem;border-bottom:1px solid var(--glass-border);">Proces</th>
                                        <th style="padding:.4rem .6rem;border-bottom:1px solid var(--glass-border);">Status</th>
                                        <th style="padding:.4rem .6rem;border-bottom:1px solid var(--glass-border);">Ostrzeżenia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(array_slice($preview, 0, $displayLimit) as $i => $row)
                                        @php
                                            $hasPhone = ($row['candidate_action'] ?? null) !== 'skip';
                                            $isNew = ($row['candidate_action'] ?? null) === 'create';
                                        @endphp
                                        <tr style="border-bottom:1px solid var(--glass-border);{{ !$hasPhone ? 'opacity:.45;' : '' }}{{ !empty($row['warnings']) ? 'background:rgba(251,191,36,.06);' : '' }}">
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
                                            <td style="padding:.35rem .6rem;">
                                                @if(!$hasPhone)
                                                    <span class="badge" style="background:rgba(239,68,68,.2);color:#f87171;font-size:.62rem;">Brak telefonu</span>
                                                @elseif($isNew)
                                                    <span class="badge" style="background:rgba(52,211,153,.15);color:#34d399;font-size:.62rem;">Nowy</span>
                                                @else
                                                    <span class="badge" style="background:rgba(96,165,250,.15);color:#60a5fa;font-size:.62rem;">Wzbogacenie</span>
                                                @endif
                                            </td>
                                            <td style="padding:.35rem .6rem;color:var(--text-muted);">
                                                {{ ($row['process_action'] ?? '') === 'reuse' ? 'Ponowne użycie' : 'Nowy' }}
                                            </td>
                                            <td style="padding:.35rem .6rem;color:var(--text-muted);">
                                                {{ $row['resolved_status_label'] ?? '—' }}
                                            </td>
                                            <td style="padding:.35rem .6rem;color:#fbbf24;">
                                                @if(!empty($row['warnings']))
                                                    <span title="{{ implode(' | ', $row['warnings']) }}">
                                                        <i class="bi bi-exclamation-triangle me-1"></i>{{ count($row['warnings']) }}
                                                    </span>
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
                    @if(!empty($preview) && ! $importing)
                        @php $toImport = collect($preview)->where('candidate_action', '!=', 'skip')->count(); @endphp
                        <button type="button" wire:click="doImport"
                                wire:loading.attr="disabled"
                                @disabled($toImport === 0)
                                class="btn btn-primary">
                            <span wire:loading.remove wire:target="doImport">
                                <i class="bi bi-check-lg me-1"></i>Importuj {{ $toImport }} wierszy
                            </span>
                            <span wire:loading wire:target="doImport">
                                <i class="bi bi-hourglass-split me-1"></i>Startuję import…
                            </span>
                        </button>
                    @endif
                    <button type="button" wire:click="closeModal" class="btn btn-outline-secondary"
                            @disabled($importing)
                            @if($importing) title="Poczekaj na zakończenie importu" @endif>
                        {{ $importing ? 'Import w toku…' : 'Anuluj' }}
                    </button>
                </div>

            </div>
        </div>
    </div>
    @endteleport
    @endif
</div>
