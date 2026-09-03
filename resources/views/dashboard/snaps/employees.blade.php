@php
    use App\Enums\EmployeeLocationState;
    $rows = $snaps['employeeRows'];
    $card = $snaps['employeeCard'];
    $inBaseOrTransit = fn ($state) => in_array($state, [EmployeeLocationState::IN_BASE, EmployeeLocationState::IN_TRANSIT], true);
@endphp

<x-dashboard.snap
    kicker="Kadry"
    title="Pracownicy — stan na dzień"
    caption="Lista pokazuje, gdzie jest ktoś dziś (albo na wybraną datę): baza / teren / w podróży, rotacja, dom, auto, projekt. Obok kartoteka tej samej osoby — dokumenty i przypisanie."
    :href="Route::has('employees.index') ? route('employees.index') : null"
    tall
>
    <x-slot:note>
        Stan na {{ $snaps['today']->format('d.m.Y') }} — ten sam filtr „Stan na dzień” co na liście pracowników.
    </x-slot:note>

    <div class="row g-3">
        <div class="col-lg-7">
            <x-ui.card>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Pracownik</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Dom</th>
                                <th class="text-center">Auto</th>
                                <th class="text-center">Projekt</th>
                                <th class="text-center">Rotacja</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                @php $st = $row['locationStatus']; @endphp
                                <tr>
                                    <td>
                                        <x-employee-cell :employee="$row['employee']" :link="false" />
                                    </td>
                                    <td class="text-center">
                                        @if($st['state'] === EmployeeLocationState::IN_TRANSIT)
                                            <x-ui.badge variant="warning">🚗 W podróży</x-ui.badge>
                                        @elseif($st['state'] === EmployeeLocationState::IN_BASE)
                                            <x-ui.badge variant="success">🏠 Baza</x-ui.badge>
                                        @else
                                            <x-ui.badge variant="info">📍 Poza bazą</x-ui.badge>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($inBaseOrTransit($st['state']))
                                            <span class="text-muted">—</span>
                                        @elseif(!empty($st['accommodation_names']))
                                            <x-ui.badge variant="info">🏡 {{ $st['accommodation_names'][0] }}</x-ui.badge>
                                        @else
                                            <x-ui.badge variant="danger">❌ Brak domu</x-ui.badge>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($inBaseOrTransit($st['state']))
                                            <span class="text-muted">—</span>
                                        @elseif(!empty($st['vehicle_labels']))
                                            <x-ui.badge variant="info">🚗 {{ $st['vehicle_labels'][0] }}</x-ui.badge>
                                        @else
                                            <x-ui.badge variant="danger">❌ Brak auta</x-ui.badge>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($inBaseOrTransit($st['state']))
                                            <span class="text-muted">—</span>
                                        @elseif(!empty($st['project_names']))
                                            <x-ui.badge variant="info">🏢 {{ $st['project_names'][0] }}</x-ui.badge>
                                        @else
                                            <x-ui.badge variant="danger">❌ Brak projektu</x-ui.badge>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <x-ui.badge :variant="$row['hasActiveRotation'] ? 'success' : 'danger'">
                                            {{ $row['hasActiveRotation'] ? '✓' : '✗' }} Rotacja
                                        </x-ui.badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>
        <div class="col-lg-5">
            <x-ui.card>
                <div class="d-flex justify-content-between align-items-start mb-3 p-3 rounded" style="background:rgba(52,211,153,.08);border:1px solid rgba(52,211,153,.25);">
                    <div>
                        <x-ui.badge variant="success"><i class="bi bi-person-check me-1"></i>Zatrudniony</x-ui.badge>
                        <div class="mt-2">
                            <x-employee-cell :employee="$card['employee']" :link="false" :avatar-size="'48px'" />
                        </div>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <h5 class="fs-6">Role</h5>
                        @foreach($card['employee']->roles as $role)
                            <x-ui.badge variant="accent">{{ $role->name }}</x-ui.badge>
                        @endforeach
                    </div>
                    <div class="col-6">
                        <h5 class="fs-6">Kontakt</h5>
                        <p class="small mb-0">{{ $card['employee']->email }}</p>
                    </div>
                </div>
                <h5 class="fs-6">Aktualne przypisanie</h5>
                <p class="small mb-3">
                    <strong>{{ $card['assignment']['project'] }}</strong>
                    · {{ $card['assignment']['role'] }}<br>
                    <span class="text-muted">{{ $card['assignment']['from']->format('d.m.Y') }} – {{ $card['assignment']['to']->format('d.m.Y') }}</span>
                </p>
                <h5 class="fs-6">Rotacja</h5>
                <p class="small mb-3">
                    <x-ui.badge variant="success">{{ $card['rotation']['status'] }}</x-ui.badge>
                    {{ $card['rotation']['start']->format('d.m.Y') }} – {{ $card['rotation']['end']->format('d.m.Y') }}
                </p>
                <h5 class="fs-6">Dom / auto</h5>
                <p class="small mb-3">{{ $card['home'] }}<br>{{ $card['car'] }}</p>
                <h5 class="fs-6">Dokumenty</h5>
                <ul class="list-unstyled small mb-0">
                    @foreach($card['documents'] as $doc)
                        <li class="d-flex justify-content-between gap-2 py-1 border-bottom" style="border-color: var(--glass-border) !important;">
                            <span>{{ $doc['name'] }}</span>
                            <x-ui.badge :variant="$doc['ok'] ? 'success' : 'danger'">
                                do {{ $doc['until']->format('d.m.Y') }}
                            </x-ui.badge>
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>
        </div>
    </div>
</x-dashboard.snap>
