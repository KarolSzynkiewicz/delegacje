@php
    $w = $snaps['weekly'];
    $project = $w['projectCard'];
@endphp

<x-dashboard.snap
    kicker="Logistyka"
    title="Przegląd tygodniowy"
    caption="Centrum dowodzenia tygodnia: wyjazdy, zjazdy, kto jest na którym projekcie i co wygasa w tym miesiącu. Kliknij kafelki — to te same popovery co w prawdziwym widoku."
    :href="Route::has('weekly-overview.index') ? route('weekly-overview.index') : null"
    :interactive="true"
    tall
>
    @include('weekly-overview.partials.week-summary', [
        'returnTrips' => $w['returnTrips'],
        'allDepartures' => $w['allDepartures'],
        'transferEvents' => $w['transferEvents'],
        'employeesInFieldCount' => $w['employeesInFieldCount'],
        'employeesInFieldByProject' => $w['employeesInFieldByProject'],
        'expiringItems' => $w['expiringItems'],
        'projectsEndingThisMonth' => $w['projectsEndingThisMonth'],
    ])

    <x-ui.card label="{{ $project['name'] }} · {{ $project['location'] }}" class="mt-3">
        <div class="row g-3">
            <div class="col-md-5">
                <p class="small text-muted text-uppercase mb-2" style="letter-spacing:.06em">Zapotrzebowanie vs obsada</p>
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Rola</th>
                            <th class="text-end">Potrzeba</th>
                            <th class="text-end">Przypisani</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($project['demand'] as $row)
                            <tr>
                                <td>{{ $row['role'] }}</td>
                                <td class="text-end font-mono">{{ $row['need'] }}</td>
                                <td class="text-end">
                                    <x-ui.badge :variant="$row['have'] >= $row['need'] ? 'success' : 'warning'">
                                        {{ $row['have'] }} / {{ $row['need'] }}
                                    </x-ui.badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="col-md-7">
                <p class="small text-muted text-uppercase mb-2" style="letter-spacing:.06em">Przypisani w tym tygodniu</p>
                <div class="d-flex flex-column gap-2">
                    @foreach($project['assigned'] as $employee)
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <x-employee-cell :employee="$employee" :link="false" />
                            <x-ui.badge variant="info">{{ $employee->roles->first()->name }}</x-ui.badge>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </x-ui.card>
</x-dashboard.snap>
