@php
    /** @var array{year:int,entitlement:int,adjustments?:int,used:int,remaining:int,overused:bool,ranges:list<array{label:string,days:int}>,adjustment_rows?:list<array{id:int,days:int,signed:string,reason:string,creator:?string,created_at:?string}>}|null $leave */
    $year = $leave['year'] ?? (int) now()->year;
    $adjustments = (int) ($leave['adjustments'] ?? 0);
    $adjustmentRows = $leave['adjustment_rows'] ?? [];
@endphp

<x-ui.card class="cl-reveal">
    <div class="profile-card">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
            <div>
                <h3 class="profile-card__title mb-1">Urlop {{ $year }}</h3>
                <p class="text-muted small mb-0">
                    Saldo = wymiar + korekty − dni urlopu w siatce godzin.
                </p>
            </div>
            @if($leave)
                <a href="{{ route('mine.office-hours.index') }}" class="btn btn-sm btn-outline-secondary">
                    Siatka godzin
                </a>
            @elseif(Route::has('office-profiles.index'))
                <a href="{{ route('office-profiles.index') }}" class="btn btn-sm btn-outline-secondary">
                    Profile biurowe
                </a>
            @endif
        </div>

        @if($leave)
            <div class="profile-leave-kpis profile-leave-kpis--4">
                <div class="profile-leave-kpi">
                    <div class="profile-leave-kpi__label">Wymiar</div>
                    <div class="profile-leave-kpi__value font-mono">{{ $leave['entitlement'] }} <span>dni</span></div>
                </div>
                <div class="profile-leave-kpi">
                    <div class="profile-leave-kpi__label">Korekty</div>
                    <div class="profile-leave-kpi__value font-mono {{ $adjustments > 0 ? 'text-success' : ($adjustments < 0 ? 'text-danger' : '') }}">
                        {{ $adjustments > 0 ? '+'.$adjustments : $adjustments }} <span>dni</span>
                    </div>
                </div>
                <div class="profile-leave-kpi">
                    <div class="profile-leave-kpi__label">Wykorzystane</div>
                    <div class="profile-leave-kpi__value font-mono">{{ $leave['used'] }} <span>dni</span></div>
                </div>
                <div class="profile-leave-kpi {{ $leave['overused'] ? 'is-over' : ($leave['remaining'] === 0 ? 'is-zero' : 'is-ok') }}">
                    <div class="profile-leave-kpi__label">Pozostało</div>
                    <div class="profile-leave-kpi__value font-mono">
                        {{ $leave['remaining'] }} <span>dni</span>
                    </div>
                </div>
            </div>

            @if($adjustmentRows !== [])
                <div class="profile-list-label">Korekty w {{ $leave['year'] }}</div>
                <div class="profile-list mb-3">
                    @foreach($adjustmentRows as $adj)
                        <div class="profile-list__row">
                            <i class="bi bi-journal-plus profile-list__icon"></i>
                            <div class="profile-list__body">
                                <div class="profile-list__title font-mono {{ $adj['days'] > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $adj['signed'] }} dni
                                </div>
                                <div class="profile-list__meta">
                                    {{ $adj['reason'] }}
                                    @if(!empty($adj['creator'])) · {{ $adj['creator'] }} @endif
                                    @if(!empty($adj['created_at'])) · {{ $adj['created_at'] }} @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="profile-list-label">Wykorzystane w {{ $leave['year'] }}</div>
            @if($leave['ranges'] === [])
                <p class="text-muted small mb-0">Brak wykorzystanych dni urlopowych w tym roku.</p>
            @else
                <div class="profile-list">
                    @foreach($leave['ranges'] as $range)
                        <div class="profile-list__row">
                            <i class="bi bi-calendar-x profile-list__icon"></i>
                            <div class="profile-list__body">
                                <div class="profile-list__title font-mono">{{ $range['label'] }}</div>
                                <div class="profile-list__meta">
                                    {{ $range['days'] }} {{ $range['days'] === 1 ? 'dzień' : 'dni' }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @else
            <div class="pm-empty py-3 mb-0 text-center">
                <i class="bi bi-person-badge d-block mb-2" style="font-size:1.4rem;opacity:.55"></i>
                <div class="small mb-2">
                    Nie masz jeszcze profilu biurowego — bez niego nie liczymy urlopu.
                </div>
                @if(Route::has('office-profiles.create'))
                    <a href="{{ route('office-profiles.create') }}" class="btn btn-sm btn-primary">
                        Załóż profil biurowy
                    </a>
                @endif
            </div>
        @endif
    </div>
</x-ui.card>
