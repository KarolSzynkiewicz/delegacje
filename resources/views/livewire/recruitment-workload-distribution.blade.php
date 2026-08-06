@php
    use App\Enums\RecruitmentStatus;
@endphp
<div>
    @unless($hideTrigger)
    <button type="button" wire:click="openModal"
            class="btn btn-sm btn-outline-secondary"
            title="Podziel procesy rekrutacyjne między rekruterów">
        <i class="bi bi-people me-1"></i>Podziel pracę
    </button>
    @endunless

    @if($show)
    @teleport('body')
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.6);z-index:1060;" wire:click.self="closeModal">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content" style="background:var(--bg-card,#1e2535);border:1px solid var(--glass-border,rgba(255,255,255,.1));color:var(--text-main,#f1f5f9);">

                <div class="modal-header" style="border-color:var(--glass-border);">
                    <h5 class="modal-title">
                        <i class="bi bi-people me-2"></i>Podziel pracę rekrutacyjną
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeModal"></button>
                </div>

                <div class="modal-body">
                    @if($result)
                        <div class="alert alert-success d-flex gap-2 align-items-start" style="font-size:.9rem;">
                            <i class="bi bi-check-circle-fill flex-shrink-0 mt-1"></i>
                            <div>
                                Przypisano <strong>{{ $result['assigned'] }}</strong> procesów rekrutacyjnych.
                                <ul class="mb-0 mt-2" style="font-size:.85rem;">
                                    @foreach($result['by_recruiter'] as $row)
                                        <li><strong>{{ $row['name'] }}</strong>: {{ $row['count'] }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @else
                        {{-- Mode tabs --}}
                        <ul class="nav nav-pills mb-3" style="font-size:.85rem;">
                            <li class="nav-item">
                                <button type="button"
                                        class="nav-link {{ $mode === 'unassigned' ? 'active' : '' }}"
                                        wire:click="setMode('unassigned')">
                                    Nieprzypisane
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button"
                                        class="nav-link {{ $mode === 'vacation' ? 'active' : '' }}"
                                        wire:click="setMode('vacation')">
                                    Z urlopu
                                </button>
                            </li>
                        </ul>

                        {{-- Step indicator --}}
                        <div class="d-flex gap-2 mb-3" style="font-size:.75rem;">
                            @foreach([1 => 'Statusy', 2 => 'Rekruterzy', 3 => 'Potwierdzenie'] as $num => $label)
                                <span style="padding:2px 10px;border-radius:20px;
                                    {{ $step === $num ? 'background:rgba(var(--primary-rgb,59,130,246),.2);color:var(--primary,#3b82f6);' : ($step > $num ? 'background:rgba(52,211,153,.15);color:#34d399;' : 'background:rgba(255,255,255,.05);color:var(--text-muted);') }}">
                                    {{ $num }}. {{ $label }}
                                </span>
                            @endforeach
                        </div>

                        @if($errorMessage)
                            <div class="alert alert-danger py-2 mb-3" style="font-size:.85rem;">
                                <i class="bi bi-exclamation-triangle me-1"></i>{{ $errorMessage }}
                            </div>
                        @endif

                        {{-- Step 1: Status selection --}}
                        @if($step === 1)
                            @if($mode === 'vacation')
                                <div class="mb-3">
                                    <label class="form-label" style="font-size:.85rem;">Rekruter na urlopie</label>
                                    <select wire:model.live="fromRecruiterId" class="form-select"
                                            style="background:rgba(255,255,255,.06);border-color:var(--glass-border);color:var(--text-main);">
                                        <option value="">— wybierz —</option>
                                        @foreach($recruitersOnLeave as $rec)
                                            <option value="{{ $rec['id'] }}">{{ $rec['name'] }} ({{ $rec['total'] }} procesów)</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <label class="form-label" style="font-size:.85rem;">
                                {{ $mode === 'vacation' ? 'Statusy do przepisania' : 'Statusy do podziału' }}
                            </label>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                @foreach($assignableStatuses as $statusCase)
                                    @php
                                        $counts = $mode === 'vacation' ? $assignedCounts : $unassignedCounts;
                                        $count = $counts[$statusCase->value] ?? 0;
                                        $checked = in_array($statusCase->value, $selectedStatuses, true);
                                    @endphp
                                    <button type="button"
                                            wire:click="toggleStatus('{{ $statusCase->value }}')"
                                            class="btn btn-sm {{ $checked ? 'btn-primary' : 'btn-outline-secondary' }}"
                                            @if($count === 0) disabled @endif>
                                        {{ $statusCase->label() }}
                                        <span class="badge {{ $checked ? 'badge-light' : 'badge-info' }} ms-1">{{ $count }}</span>
                                    </button>
                                @endforeach
                            </div>

                            <div style="font-size:.85rem;padding:.75rem;border-radius:8px;background:rgba(255,255,255,.03);border:1px solid var(--glass-border);">
                                Do podziału: <strong>{{ $selectedTotal }}</strong> procesów
                            </div>
                        @endif

                        {{-- Step 2: Recruiter selection + distribution --}}
                        @if($step === 2)
                            <div class="mb-3" style="font-size:.85rem;color:var(--text-muted);">
                                Łącznie do podziału: <strong style="color:var(--text-main);">{{ $selectedTotal }}</strong> procesów
                            </div>

                            <label class="form-label" style="font-size:.85rem;">Kto ma dostać tę pracę?</label>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                @foreach($recruiters as $recruiter)
                                    @if($mode === 'vacation' && $recruiter->id === $fromRecruiterId)
                                        @continue
                                    @endif
                                    @php $checked = in_array($recruiter->id, $selectedRecruiterIds, true); @endphp
                                    <button type="button"
                                            wire:click="toggleRecruiter({{ $recruiter->id }})"
                                            class="btn btn-sm {{ $checked ? 'btn-success' : 'btn-outline-secondary' }}">
                                        @if($checked)<i class="bi bi-check-lg me-1"></i>@endif
                                        {{ $recruiter->name }}
                                    </button>
                                @endforeach
                            </div>

                            @if($selectedRecruiterIds !== [])
                                <label class="form-label" style="font-size:.85rem;">Podział (edytowalny)</label>
                                <div class="table-responsive mb-2">
                                    <table class="table table-sm mb-0" style="font-size:.85rem;">
                                        <thead>
                                            <tr style="color:var(--text-muted);font-size:.7rem;text-transform:uppercase;">
                                                <th>Rekruter</th>
                                                <th style="width:120px;">Liczba procesów</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($selectedRecruiterIds as $recruiterId)
                                                @php $recruiter = $recruiters->firstWhere('id', $recruiterId); @endphp
                                                <tr wire:key="dist-{{ $recruiterId }}">
                                                    <td>{{ $recruiter?->name ?? '—' }}</td>
                                                    <td>
                                                        <input type="number"
                                                               min="0"
                                                               wire:model.live="distribution.{{ $recruiterId }}"
                                                               class="form-control form-control-sm"
                                                               style="background:rgba(255,255,255,.06);border-color:var(--glass-border);color:var(--text-main);">
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td class="text-end" style="color:var(--text-muted);">Suma:</td>
                                                <td>
                                                    <strong class="{{ $distributionSum === $selectedTotal ? 'text-success' : 'text-danger' }}">
                                                        {{ $distributionSum }} / {{ $selectedTotal }}
                                                    </strong>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <button type="button" wire:click="recalculateDistribution" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-arrow-repeat me-1"></i>Podziel równo
                                </button>
                            @endif
                        @endif

                        {{-- Step 3: Confirmation --}}
                        @if($step === 3)
                            <div style="font-size:.85rem;padding:1rem;border-radius:8px;background:rgba(255,255,255,.03);border:1px solid var(--glass-border);">
                                <div class="mb-2">
                                    <span style="color:var(--text-muted);">Tryb:</span>
                                    <strong>{{ $mode === 'vacation' ? 'Przepisanie z urlopu' : 'Podział nieprzypisanych' }}</strong>
                                </div>
                                @if($mode === 'vacation' && $fromRecruiterId)
                                    @php $fromName = $recruiters->firstWhere('id', $fromRecruiterId)?->name; @endphp
                                    <div class="mb-2">
                                        <span style="color:var(--text-muted);">Od:</span>
                                        <strong>{{ $fromName }}</strong>
                                    </div>
                                @endif
                                <div class="mb-2">
                                    <span style="color:var(--text-muted);">Statusy:</span>
                                    @foreach($selectedStatuses as $statusVal)
                                        <span class="badge badge-info me-1">{{ RecruitmentStatus::from($statusVal)->label() }}</span>
                                    @endforeach
                                </div>
                                <div class="mb-3">
                                    <span style="color:var(--text-muted);">Łącznie:</span>
                                    <strong>{{ $selectedTotal }}</strong> procesów
                                </div>
                                <div>
                                    <span style="color:var(--text-muted);">Podział:</span>
                                    <ul class="mb-0 mt-1">
                                        @foreach($selectedRecruiterIds as $recruiterId)
                                            @php $recruiter = $recruiters->firstWhere('id', $recruiterId); @endphp
                                            <li><strong>{{ $recruiter?->name }}</strong>: {{ $distribution[$recruiterId] ?? 0 }} procesów</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>

                <div class="modal-footer" style="border-color:var(--glass-border);">
                    @if($result)
                        <button type="button" wire:click="closeModal" class="btn btn-primary">Zamknij</button>
                    @else
                        @if($step > 1)
                            <button type="button" wire:click="previousStep" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Wstecz
                            </button>
                        @endif

                        @if($step < 3)
                            <button type="button" wire:click="nextStep" class="btn btn-primary">
                                Dalej<i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        @else
                            <button type="button"
                                    wire:click="confirmDistribution"
                                    wire:loading.attr="disabled"
                                    class="btn btn-primary">
                                <span wire:loading.remove wire:target="confirmDistribution">
                                    <i class="bi bi-check-lg me-1"></i>Zatwierdź przypisanie
                                </span>
                                <span wire:loading wire:target="confirmDistribution">
                                    <i class="bi bi-hourglass-split me-1"></i>Przypisuję…
                                </span>
                            </button>
                        @endif

                        <button type="button" wire:click="closeModal" class="btn btn-outline-secondary">Anuluj</button>
                    @endif
                </div>

            </div>
        </div>
    </div>
    @endteleport
    @endif
</div>
