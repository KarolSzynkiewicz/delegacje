<div
    class="office-profiles-table"
    x-data="{ savedId: null, savedTimer: null }"
    x-on:office-profile-saved.window="
        savedId = $event.detail.userId;
        clearTimeout(savedTimer);
        savedTimer = setTimeout(() => savedId = null, 1200);
    "
>
    <x-data-table :paginator="$users" :has-filters="$search !== '' || $scope !== 'all'">
        <x-slot:filters>
            <x-data-table-filters :count="$users->total()">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    class="form-control form-control-sm"
                    placeholder="Szukaj: imię, e-mail…"
                    autocomplete="off"
                    aria-label="Szukaj"
                >

                <select wire:model.live="scope" class="form-select form-select-sm" aria-label="Zakres">
                    <option value="all">Wszyscy użytkownicy</option>
                    <option value="office">Tylko aktywni w biurze</option>
                    <option value="none">Bez aktywnego profilu</option>
                </select>

                @if($search !== '' || $scope !== 'all')
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="clearFilters">
                        Wyczyść
                    </button>
                @endif
            </x-data-table-filters>
        </x-slot:filters>

        <x-slot:head>
            <tr>
                <th>Osoba</th>
                <th class="text-center" style="width:5rem">Biuro</th>
                <th style="min-width:8.5rem">Umowa</th>
                <th style="min-width:9.5rem">Rozliczenie</th>
                <th style="min-width:5.5rem">Etat</th>
                <th style="min-width:6.5rem">Stawka</th>
                <th style="min-width:4.75rem">Waluta</th>
                <th style="min-width:9rem">Od</th>
                <th style="min-width:9rem">Do</th>
                <th>Status</th>
            </tr>
        </x-slot:head>

        <x-slot:body>
            @foreach($users as $user)
                @php
                    $row = $rows[$user->id] ?? null;
                    $enabled = (bool) ($row['enabled'] ?? false);
                    $dim = $enabled ? '' : 'opacity-50';
                @endphp
                <tr
                    wire:key="office-user-{{ $user->id }}"
                    x-bind:class="savedId == {{ $user->id }} ? 'office-row-saved' : ''"
                >
                    <td>
                        <div class="fw-semibold">{{ $user->name }}</div>
                        <div class="small text-muted">{{ $user->email }}</div>
                    </td>
                    <td class="text-center">
                        <div class="form-check form-switch d-inline-flex justify-content-center m-0">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                wire:model.live="rows.{{ $user->id }}.enabled"
                                @disabled(! $canUpdate)
                                aria-label="Profil biurowy"
                            >
                        </div>
                    </td>
                    <td class="{{ $dim }}">
                        <select
                            class="form-select form-select-sm"
                            wire:model.live="rows.{{ $user->id }}.contract_type"
                            @disabled(! $canUpdate || ! $enabled)
                        >
                            @foreach($contractTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="{{ $dim }}">
                        <select
                            class="form-select form-select-sm"
                            wire:model.live="rows.{{ $user->id }}.pay_mode"
                            @disabled(! $canUpdate || ! $enabled)
                        >
                            @foreach($payModes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="{{ $dim }}">
                        <input
                            type="number"
                            min="0.01"
                            max="1"
                            step="0.01"
                            class="form-control form-control-sm font-mono"
                            wire:model.blur="rows.{{ $user->id }}.fte"
                            @disabled(! $canUpdate || ! $enabled)
                        >
                        @error('rows.'.$user->id.'.fte')
                            <div class="small text-danger">{{ $message }}</div>
                        @enderror
                    </td>
                    <td class="{{ $dim }}">
                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            class="form-control form-control-sm font-mono"
                            wire:model.blur="rows.{{ $user->id }}.hourly_rate"
                            @disabled(! $canUpdate || ! $enabled)
                            placeholder="—"
                        >
                    </td>
                    <td class="{{ $dim }}">
                        <input
                            type="text"
                            maxlength="3"
                            class="form-control form-control-sm font-mono text-uppercase"
                            wire:model.blur="rows.{{ $user->id }}.currency"
                            @disabled(! $canUpdate || ! $enabled)
                        >
                    </td>
                    <td class="{{ $dim }}">
                        <input
                            type="date"
                            class="form-control form-control-sm"
                            wire:model.blur="rows.{{ $user->id }}.started_on"
                            @disabled(! $canUpdate || ! $enabled)
                        >
                    </td>
                    <td class="{{ $dim }}">
                        <input
                            type="date"
                            class="form-control form-control-sm"
                            wire:model.blur="rows.{{ $user->id }}.ended_on"
                            @disabled(! $canUpdate || ! $enabled)
                        >
                    </td>
                    <td>
                        @if($enabled)
                            <x-ui.badge variant="success">Aktywny</x-ui.badge>
                        @elseif($user->officeProfile)
                            <x-ui.badge variant="secondary">Nieaktywny</x-ui.badge>
                        @else
                            <x-ui.badge variant="secondary">Brak</x-ui.badge>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-slot:body>
    </x-data-table>

    <style>
        .office-row-saved td { box-shadow: inset 0 -2px 0 var(--primary, #3b82f6); }
        .office-profiles-table .form-control:disabled,
        .office-profiles-table .form-select:disabled {
            opacity: 0.55;
        }
        .office-profiles-table td {
            padding: 0.55rem 0.5rem;
            vertical-align: middle;
        }
        .office-profiles-table .form-control-sm,
        .office-profiles-table .form-select-sm {
            padding: 0.35rem 0.65rem;
            min-height: 2rem;
            line-height: 1.25;
        }
        .office-profiles-table .form-select-sm {
            padding-right: 2rem;
            background-position: right 0.55rem center;
        }
        .office-profiles-table input.font-mono {
            padding-left: 0.7rem;
            padding-right: 0.7rem;
        }
        .office-profiles-table input[type="date"].form-control-sm {
            padding-left: 0.55rem;
            padding-right: 0.35rem;
            min-width: 8.75rem;
        }
    </style>
</div>
