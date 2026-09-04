@php
    $terminated = $employee->isTerminated();
@endphp
<article class="card emp-hero {{ $terminated ? 'emp-hero--out' : 'emp-hero--in' }}">
    <div class="emp-hero__photo-frame" aria-hidden="{{ $employee->image_path ? 'false' : 'true' }}">
        @if($employee->image_path)
            <img
                src="{{ $employee->image_url }}"
                alt="{{ $employee->full_name }}"
                class="emp-hero__photo"
            >
        @else
            <div class="emp-hero__photo emp-hero__photo--empty">{{ $initials }}</div>
        @endif
    </div>

    <div class="emp-hero__body">
        <div class="emp-hero__bar">
            <div class="emp-hero__status">
                @if($terminated)
                    <x-ui.badge variant="danger">
                        <i class="bi bi-person-x me-1"></i>Zwolniony
                    </x-ui.badge>
                    <span class="emp-hero__status-meta">
                        {{ $employee->terminated_at->format('Y-m-d') }}
                        · {{ $employee->termination_reason?->label() ?? '-' }}
                    </span>
                    @if($employee->termination_note)
                        <span class="emp-hero__status-note">{{ $employee->termination_note }}</span>
                    @endif
                @else
                    <x-ui.badge variant="success">
                        <i class="bi bi-person-check me-1"></i>Zatrudniony
                    </x-ui.badge>
                @endif
                <span class="emp-hero__id font-mono">#{{ $employee->id }}</span>
            </div>

            <div class="emp-hero__actions">
                @if(auth()->user()->hasPermission('employees.update'))
                    <x-ui.button variant="ghost" href="{{ route('employees.edit', $employee) }}" class="btn-sm">
                        <i class="bi bi-pencil me-1"></i>Edytuj
                    </x-ui.button>
                    @if($terminated)
                        <x-ui.button variant="outline-secondary" class="btn-sm" wire:click="reinstate" wire:confirm="Czy na pewno chcesz cofnąć zwolnienie tego pracownika?">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Cofnij zwolnienie
                        </x-ui.button>
                    @else
                        <x-ui.button variant="danger" class="btn-sm" wire:click="openTerminateModal">
                            <i class="bi bi-person-x me-1"></i>Zwolnij
                        </x-ui.button>
                    @endif
                @endif
            </div>
        </div>

        <h1 class="emp-hero__name">{{ $employee->full_name }}</h1>

        <div class="emp-hero__roles">
            @if($employee->roles->count() > 0)
                @foreach($employee->roles as $role)
                    <x-ui.badge variant="accent">{{ $role->name }}</x-ui.badge>
                @endforeach
            @else
                <span class="text-muted">Brak przypisanych ról</span>
            @endif
        </div>

        <div class="emp-hero__location">
            @if($locationStatus['state'] === \App\Enums\EmployeeLocationState::IN_TRANSIT)
                <x-tooltip title="Pracownik jest w trakcie wyjazdu/powrotu">
                    <x-ui.badge variant="warning">🚗 W podróży</x-ui.badge>
                </x-tooltip>
            @elseif($locationStatus['state'] === \App\Enums\EmployeeLocationState::IN_BASE)
                <x-tooltip title="Pracownik jest w bazie">
                    <x-ui.badge variant="success">🏠 Baza</x-ui.badge>
                </x-tooltip>
            @elseif(count($locationStatus['accommodation_names'] ?? []) > 0 || count($locationStatus['project_names'] ?? []) > 0 || count($locationStatus['vehicle_labels'] ?? []) > 0)
                @if($locationStatus['has_assignment_overlap'] ?? false)
                    <div class="small text-warning mb-1">⚠ Wiele aktywnych przypisań tego dnia — sprawdź dane.</div>
                @endif
                <div class="d-flex flex-wrap gap-1 align-items-center">
                    @foreach($locationStatus['accommodation_names'] ?? [] as $n)
                        <x-ui.badge :variant="($locationStatus['has_assignment_overlap'] ?? false) ? 'warning' : 'info'">🏡 {{ $n }}</x-ui.badge>
                    @endforeach
                    @foreach($locationStatus['vehicle_labels'] ?? [] as $reg)
                        <x-ui.badge :variant="($locationStatus['has_assignment_overlap'] ?? false) ? 'warning' : 'info'">🚗 {{ $reg }}</x-ui.badge>
                    @endforeach
                    @foreach($locationStatus['project_names'] ?? [] as $pn)
                        <x-ui.badge :variant="($locationStatus['has_assignment_overlap'] ?? false) ? 'warning' : 'info'">🏢 {{ $pn }}</x-ui.badge>
                    @endforeach
                </div>
            @else
                <x-tooltip title="Pracownik jest poza bazą, oczekuje na przypisania">
                    <x-ui.badge variant="accent">⏳ Poza bazą</x-ui.badge>
                </x-tooltip>
            @endif
        </div>

        <div class="emp-hero__contact">
            @if($employee->phone)
                <a href="tel:{{ $employee->phone }}" class="emp-hero__contact-row">
                    <span class="emp-hero__contact-icon"><i class="bi bi-telephone"></i></span>
                    <span>{{ $employee->phone }}</span>
                </a>
            @endif
            @if($employee->email)
                <a href="mailto:{{ $employee->email }}" class="emp-hero__contact-row">
                    <span class="emp-hero__contact-icon"><i class="bi bi-envelope"></i></span>
                    <span class="text-truncate">{{ $employee->email }}</span>
                </a>
            @endif
        </div>
    </div>
</article>
