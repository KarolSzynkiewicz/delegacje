<div class="office-leave">
    <div class="d-flex flex-wrap gap-2 align-items-end justify-content-between mb-3">
        <div class="d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label small mb-1" for="ol-year">Rok</label>
                <input
                    id="ol-year"
                    type="number"
                    class="form-control form-control-sm font-mono"
                    style="width:6rem"
                    wire:model.live="year"
                    min="2000"
                    max="2100"
                >
            </div>
            <div>
                <label class="form-label small mb-1" for="ol-search">Szukaj</label>
                <input
                    id="ol-search"
                    type="search"
                    class="form-control form-control-sm"
                    style="min-width:14rem"
                    placeholder="Imię lub e-mail…"
                    wire:model.live.debounce.300ms="search"
                >
            </div>
        </div>
        <div class="small text-muted">
            Saldo = wymiar + korekty − dni urlopu w siatce godzin.
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-{{ $selected ? '7' : '12' }}">
            <div class="table-responsive">
                <table class="table align-middle mb-0 ol-table">
                    <thead>
                        <tr>
                            <th>Osoba</th>
                            <th class="text-end font-mono">Wymiar</th>
                            <th class="text-end font-mono">Korekty</th>
                            <th class="text-end font-mono">Wykorz.</th>
                            <th class="text-end font-mono">Pozostało</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($profiles as $row)
                            @php
                                $profile = $row['profile'];
                                $b = $row['balance'];
                                $active = $selectedProfileId === $profile->id;
                            @endphp
                            <tr class="{{ $active ? 'ol-row--active' : '' }}" wire:key="ol-row-{{ $profile->id }}">
                                <td>
                                    <div class="fw-semibold">{{ $profile->user?->name ?? '—' }}</div>
                                    <div class="small text-muted">{{ $profile->user?->email }}</div>
                                </td>
                                <td class="text-end font-mono">{{ $b['entitlement'] }}</td>
                                <td class="text-end font-mono {{ $b['adjustments'] > 0 ? 'text-success' : ($b['adjustments'] < 0 ? 'text-danger' : '') }}">
                                    {{ $b['adjustments'] > 0 ? '+'.$b['adjustments'] : $b['adjustments'] }}
                                </td>
                                <td class="text-end font-mono">{{ $b['used'] }}</td>
                                <td class="text-end font-mono fw-semibold {{ $b['overused'] ? 'text-danger' : '' }}">
                                    {{ $b['remaining'] }}
                                </td>
                                <td class="text-end">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        wire:click="selectProfile({{ $profile->id }})"
                                    >
                                        {{ $active ? 'Otwarte' : 'Zarządzaj' }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-muted text-center py-4">
                                    Brak aktywnych profili biurowych.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $profiles->links() }}</div>
        </div>

        @if($selected && $selectedBalance)
            <div class="col-lg-5">
                <x-ui.card class="ol-panel">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                        <div>
                            <h3 class="h6 mb-1">{{ $selected->user?->name }}</h3>
                            <div class="small text-muted font-mono">Rok {{ $selectedBalance['year'] }}</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="clearSelection">
                            Zamknij
                        </button>
                    </div>

                    <div class="profile-leave-kpis mb-3">
                        <div class="profile-leave-kpi">
                            <div class="profile-leave-kpi__label">Wymiar</div>
                            <div class="profile-leave-kpi__value font-mono">{{ $selectedBalance['entitlement'] }}</div>
                        </div>
                        <div class="profile-leave-kpi">
                            <div class="profile-leave-kpi__label">Korekty</div>
                            <div class="profile-leave-kpi__value font-mono">
                                {{ $selectedBalance['adjustments'] > 0 ? '+'.$selectedBalance['adjustments'] : $selectedBalance['adjustments'] }}
                            </div>
                        </div>
                        <div class="profile-leave-kpi {{ $selectedBalance['overused'] ? 'is-over' : 'is-ok' }}">
                            <div class="profile-leave-kpi__label">Pozostało</div>
                            <div class="profile-leave-kpi__value font-mono">{{ $selectedBalance['remaining'] }}</div>
                        </div>
                    </div>

                    @if($canManage)
                        <form wire:submit="addAdjustment" class="ol-form mb-3">
                            <div class="row g-2">
                                <div class="col-4">
                                    <label class="form-label small mb-1" for="ol-days">Dni (+/−)</label>
                                    <input id="ol-days" type="number" class="form-control form-control-sm font-mono" wire:model="days" min="-366" max="366" required>
                                    @error('days') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-8">
                                    <label class="form-label small mb-1" for="ol-effective">Data korekty</label>
                                    <input id="ol-effective" type="date" class="form-control form-control-sm font-mono" wire:model="effectiveOn">
                                    @error('effectiveOn') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label small mb-1" for="ol-reason">Powód (audyt)</label>
                                    <input id="ol-reason" type="text" class="form-control form-control-sm" wire:model="reason" maxlength="500" required placeholder="np. przeniesienie z 2025, korekta HR…">
                                    @error('reason') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-sm btn-primary" wire:loading.attr="disabled">
                                        Zapisz korektę
                                    </button>
                                </div>
                            </div>
                        </form>
                    @endif

                    <div class="profile-list-label">Korekty w {{ $selectedBalance['year'] }}</div>
                    @if($selectedBalance['adjustment_rows'] === [])
                        <p class="small text-muted mb-3">Brak korekt w tym roku.</p>
                    @else
                        <div class="profile-list mb-3">
                            @foreach($selectedBalance['adjustment_rows'] as $adj)
                                <div class="profile-list__row" wire:key="ol-adj-{{ $adj['id'] }}">
                                    <div class="profile-list__body">
                                        <div class="profile-list__title font-mono {{ $adj['days'] > 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $adj['signed'] }} dni
                                        </div>
                                        <div class="profile-list__meta">
                                            {{ $adj['reason'] }}
                                            @if($adj['creator']) · {{ $adj['creator'] }} @endif
                                            @if($adj['created_at']) · {{ $adj['created_at'] }} @endif
                                        </div>
                                    </div>
                                    @if($canManage)
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-secondary"
                                            wire:click="deleteAdjustment({{ $adj['id'] }})"
                                            wire:confirm="Usunąć tę korektę z audytu?"
                                            title="Usuń"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="profile-list-label">Urlopy wykorzystane</div>
                    @if($selectedBalance['ranges'] === [])
                        <p class="small text-muted mb-0">Brak dni Leave w siatce w tym roku.</p>
                    @else
                        <div class="profile-list">
                            @foreach($selectedBalance['ranges'] as $range)
                                <div class="profile-list__row">
                                    <i class="bi bi-calendar-x profile-list__icon"></i>
                                    <div class="profile-list__body">
                                        <div class="profile-list__title font-mono">{{ $range['label'] }}</div>
                                        <div class="profile-list__meta">{{ $range['days'] }} dni</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-ui.card>
            </div>
        @endif
    </div>

    <style>
        .ol-table thead th {
            font-size: 0.72rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--text-muted);
            border-bottom-color: rgba(255,255,255,0.08);
        }
        .ol-row--active td {
            background: rgba(168, 85, 247, 0.08);
        }
        .ol-panel .profile-leave-kpis {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    </style>
</div>
