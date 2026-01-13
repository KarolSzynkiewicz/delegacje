<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-semibold fs-4 text-dark mb-0">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <x-ui.card class="mb-4">
                <h3 class="fs-5 fw-semibold mb-3">Witaj w systemie zarządzania logistyką!</h3>
                <p class="mb-0">System Stocznia - zarządzanie projektami, pracownikami i zasobami.</p>
            </x-ui.card>
            
            <div class="row g-4 mb-4">
                <!-- Widok Tygodniowy -->
                <div class="col-md-6 col-lg-4">
                    <x-ui.card class="h-100 dashboard-card" style="cursor: pointer;" onclick="window.location.href='{{ route('weekly-overview.index') }}'">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-calendar-week text-info fs-2 me-3"></i>
                            <h4 class="card-title mb-0 text-info fw-semibold">Przegląd Tygodniowy</h4>
                        </div>
                        <p class="card-text text-muted mb-2">Tygodniowy podgląd przydziałów ekip</p>
                        <p class="card-text small text-muted mb-0">Projekty • Pracownicy • Domy • Auta</p>
                    </x-ui.card>
                </div>

                <!-- Projekty -->
                <div class="col-md-6 col-lg-4">
                    <x-ui.card class="h-100 dashboard-card" style="cursor: pointer;" onclick="window.location.href='{{ route('projects.index') }}'">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-folder text-primary fs-2 me-3"></i>
                            <h4 class="card-title mb-0 text-primary fw-semibold">Projekty</h4>
                        </div>
                        <p class="card-text text-muted mb-0">Zarządzaj projektami i zapotrzebowaniem</p>
                    </x-ui.card>
                </div>

                <!-- Przypisania -->
                <div class="col-md-6 col-lg-4">
                    <x-ui.card class="h-100 dashboard-card" style="cursor: pointer;" onclick="window.location.href='{{ route('project-assignments.index') }}'">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-person-check text-success fs-2 me-3"></i>
                            <h4 class="card-title mb-0 text-success fw-semibold">Przypisania</h4>
                        </div>
                        <p class="card-text text-muted mb-0">Przypisania pracowników do projektów</p>
                    </x-ui.card>
                </div>

                <!-- Pracownicy -->
                <div class="col-md-6 col-lg-4">
                    <x-ui.card class="h-100 dashboard-card" style="cursor: pointer;" onclick="window.location.href='{{ route('employees.index') }}'">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-people text-purple fs-2 me-3"></i>
                            <h4 class="card-title mb-0 text-purple fw-semibold">Pracownicy</h4>
                        </div>
                        <p class="card-text text-muted mb-0">Zarządzaj bazą pracowników</p>
                    </x-ui.card>
                </div>

                <!-- Rotacje Pracowników -->
                <div class="col-md-6 col-lg-4">
                    <x-ui.card class="h-100 dashboard-card" style="cursor: pointer;" onclick="window.location.href='{{ route('rotations.index') }}'">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-arrow-repeat text-info fs-2 me-3"></i>
                            <h4 class="card-title mb-0 text-info fw-semibold">Rotacje Pracowników</h4>
                        </div>
                        <p class="card-text text-muted mb-0">Zarządzaj rotacjami dostępności</p>
                    </x-ui.card>
                </div>

                <!-- Pojazdy -->
                <div class="col-md-6 col-lg-4">
                    <x-ui.card class="h-100 dashboard-card" style="cursor: pointer;" onclick="window.location.href='{{ route('vehicles.index') }}'">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-car-front text-warning fs-2 me-3"></i>
                            <h4 class="card-title mb-0 text-warning fw-semibold">Pojazdy</h4>
                        </div>
                        <p class="card-text text-muted mb-0">Zarządzaj flotą pojazdów</p>
                    </x-ui.card>
                </div>

                <!-- Mieszkania -->
                <div class="col-md-6 col-lg-4">
                    <x-ui.card class="h-100 dashboard-card" style="cursor: pointer;" onclick="window.location.href='{{ route('accommodations.index') }}'">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-house text-danger fs-2 me-3"></i>
                            <h4 class="card-title mb-0 text-danger fw-semibold">Mieszkania</h4>
                        </div>
                        <p class="card-text text-muted mb-0">Zarządzaj akomodacjami</p>
                    </x-ui.card>
                </div>

                <!-- Przypisania Pojazdów -->
                <div class="col-md-6 col-lg-4">
                    <x-ui.card class="h-100 dashboard-card" style="cursor: pointer;" onclick="window.location.href='{{ route('vehicle-assignments.index') }}'">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-car-front-fill text-primary fs-2 me-3"></i>
                            <h4 class="card-title mb-0 text-primary fw-semibold">Przypisania Pojazdów</h4>
                        </div>
                        <p class="card-text text-muted mb-0">Przypisania pracownik-pojazd</p>
                    </x-ui.card>
                </div>

                <!-- Przypisania Mieszkań -->
                <div class="col-md-6 col-lg-4">
                    <x-ui.card class="h-100 dashboard-card" style="cursor: pointer;" onclick="window.location.href='{{ route('accommodation-assignments.index') }}'">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-house-fill text-danger fs-2 me-3"></i>
                            <h4 class="card-title mb-0 text-danger fw-semibold">Przypisania Mieszkań</h4>
                        </div>
                        <p class="card-text text-muted mb-0">Przypisania pracownik-mieszkanie</p>
                    </x-ui.card>
                </div>

                <!-- Lokalizacje -->
                <div class="col-md-6 col-lg-4">
                    <x-ui.card class="h-100 dashboard-card" style="cursor: pointer;" onclick="window.location.href='{{ route('locations.index') }}'">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-geo-alt text-success fs-2 me-3"></i>
                            <h4 class="card-title mb-0 text-success fw-semibold">Lokalizacje</h4>
                        </div>
                        <p class="card-text text-muted mb-0">Zarządzaj lokalizacjami projektów</p>
                    </x-ui.card>
                </div>

                <!-- Role -->
                <div class="col-md-6 col-lg-4">
                    <x-ui.card class="h-100 dashboard-card" style="cursor: pointer;" onclick="window.location.href='{{ route('roles.index') }}'">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-person-badge text-warning fs-2 me-3"></i>
                            <h4 class="card-title mb-0 text-warning fw-semibold">Role</h4>
                        </div>
                        <p class="card-text text-muted mb-0">Zarządzaj rolami w projektach</p>
                    </x-ui.card>
                </div>

                <!-- Użytkownicy -->
                <div class="col-md-6 col-lg-4">
                    <x-ui.card class="h-100 dashboard-card" style="cursor: pointer;" onclick="window.location.href='{{ route('users.index') }}'">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-person-gear text-primary fs-2 me-3"></i>
                            <h4 class="card-title mb-0 text-primary fw-semibold">Użytkownicy</h4>
                        </div>
                        <p class="card-text text-muted mb-0">Zarządzaj użytkownikami systemu</p>
                    </x-ui.card>
                </div>

                <!-- Role Użytkowników -->
                <div class="col-md-6 col-lg-4">
                    <x-ui.card class="h-100 dashboard-card" style="cursor: pointer;" onclick="window.location.href='{{ route('user-roles.index') }}'">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-shield-check text-primary fs-2 me-3"></i>
                            <h4 class="card-title mb-0 text-primary fw-semibold">Role Użytkowników</h4>
                        </div>
                        <p class="card-text text-muted mb-0">Zarządzaj rolami i uprawnieniami</p>
                    </x-ui.card>
                </div>

                <!-- Wymagania formalne -->
                <div class="col-md-6 col-lg-4">
                    <x-ui.card class="h-100 dashboard-card" style="cursor: pointer;" onclick="window.location.href='{{ route('documents.index') }}'">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-file-earmark-text text-primary fs-2 me-3"></i>
                            <h4 class="card-title mb-0 text-primary fw-semibold">Wymagania formalne</h4>
                        </div>
                        <p class="card-text text-muted mb-0">Zarządzaj wymaganiami formalnymi</p>
                    </x-ui.card>
                </div>

                <!-- Dokumenty Pracowników -->
                <div class="col-md-6 col-lg-4">
                    <x-ui.card class="h-100 dashboard-card" style="cursor: pointer;" onclick="window.location.href='{{ route('employee-documents.index') }}'">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-file-earmark-medical text-warning fs-2 me-3"></i>
                            <h4 class="card-title mb-0 text-warning fw-semibold">Dokumenty Pracowników</h4>
                        </div>
                        <p class="card-text text-muted mb-0">Zarządzaj dokumentami pracowników</p>
                    </x-ui.card>
                </div>

                <!-- Zjazdy -->
                @can('viewAny', \App\Models\LogisticsEvent::class)
                <div class="col-md-6 col-lg-4">
                    <x-ui.card class="h-100 dashboard-card" style="cursor: pointer;" onclick="window.location.href='{{ route('return-trips.index') }}'">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-arrow-left-right text-secondary fs-2 me-3"></i>
                            <h4 class="card-title mb-0 text-secondary fw-semibold">Zjazdy</h4>
                        </div>
                        <p class="card-text text-muted mb-0">Zarządzaj zjazdami pracowników do bazy</p>
                    </x-ui.card>
                </div>
                @endcan

                <!-- Sprzęt -->
                @can('viewAny', \App\Models\Equipment::class)
                <div class="col-md-6 col-lg-4">
                    <x-ui.card class="h-100 dashboard-card" style="cursor: pointer;" onclick="window.location.href='{{ route('equipment.index') }}'">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-tools text-success fs-2 me-3"></i>
                            <h4 class="card-title mb-0 text-success fw-semibold">Sprzęt</h4>
                        </div>
                        <p class="card-text text-muted mb-0">Zarządzaj sprzętem i magazynem</p>
                    </x-ui.card>
                </div>
                @endcan

                <!-- Wydania Sprzętu -->
                @can('viewAny', \App\Models\EquipmentIssue::class)
                <div class="col-md-6 col-lg-4">
                    <x-ui.card class="h-100 dashboard-card" style="cursor: pointer;" onclick="window.location.href='{{ route('equipment-issues.index') }}'">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-box-arrow-up text-success fs-2 me-3"></i>
                            <h4 class="card-title mb-0 text-success fw-semibold">Wydania Sprzętu</h4>
                        </div>
                        <p class="card-text text-muted mb-0">Wydania i zwroty sprzętu</p>
                    </x-ui.card>
                </div>
                @endcan

                <!-- Koszty Transportu -->
                @can('viewAny', \App\Models\TransportCost::class)
                <div class="col-md-6 col-lg-4">
                    <x-ui.card class="h-100 dashboard-card" style="cursor: pointer;" onclick="window.location.href='{{ route('transport-costs.index') }}'">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-currency-dollar text-danger fs-2 me-3"></i>
                            <h4 class="card-title mb-0 text-danger fw-semibold">Koszty Transportu</h4>
                        </div>
                        <p class="card-text text-muted mb-0">Ewidencja kosztów transportu</p>
                    </x-ui.card>
                </div>
                @endcan

                <!-- Ewidencja Godzin -->
                @can('viewAny', \App\Models\TimeLog::class)
                <div class="col-md-6 col-lg-4">
                    <x-ui.card class="h-100 dashboard-card" style="cursor: pointer;" onclick="window.location.href='{{ route('time-logs.index') }}'">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-clock text-info fs-2 me-3"></i>
                            <h4 class="card-title mb-0 text-info fw-semibold">Ewidencja Godzin</h4>
                        </div>
                        <p class="card-text text-muted mb-0">Rejestracja rzeczywistych godzin pracy</p>
                    </x-ui.card>
                </div>
                @endcan

                <!-- Stawki Pracowników -->
                <div class="col-md-6 col-lg-4">
                    <x-ui.card class="h-100 dashboard-card" style="cursor: pointer;" onclick="window.location.href='{{ route('employee-rates.index') }}'">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-currency-exchange text-success fs-2 me-3"></i>
                            <h4 class="card-title mb-0 text-success fw-semibold">Stawki Pracowników</h4>
                        </div>
                        <p class="card-text text-muted mb-0">Zarządzaj stawkami pracowników</p>
                    </x-ui.card>
                </div>

                <!-- Payroll -->
                <div class="col-md-6 col-lg-4">
                    <x-ui.card class="h-100 dashboard-card" style="cursor: pointer;" onclick="window.location.href='{{ route('payrolls.index') }}'">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-cash-stack text-primary fs-2 me-3"></i>
                            <h4 class="card-title mb-0 text-primary fw-semibold">Payroll</h4>
                        </div>
                        <p class="card-text text-muted mb-0">Rozliczenia pracowników</p>
                    </x-ui.card>
                </div>

                <!-- Kary i Nagrody -->
                <div class="col-md-6 col-lg-4">
                    <x-ui.card class="h-100 dashboard-card" style="cursor: pointer;" onclick="window.location.href='{{ route('adjustments.index') }}'">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-exclamation-triangle text-warning fs-2 me-3"></i>
                            <h4 class="card-title mb-0 text-warning fw-semibold">Kary i Nagrody</h4>
                        </div>
                        <p class="card-text text-muted mb-0">Zarządzaj karami i nagrodami</p>
                    </x-ui.card>
                </div>

                <!-- Zaliczki -->
                <div class="col-md-6 col-lg-4">
                    <x-ui.card class="h-100 dashboard-card" style="cursor: pointer;" onclick="window.location.href='{{ route('advances.index') }}'">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-wallet text-info fs-2 me-3"></i>
                            <h4 class="card-title mb-0 text-info fw-semibold">Zaliczki</h4>
                        </div>
                        <p class="card-text text-muted mb-0">Zarządzaj zaliczkami pracowników</p>
                    </x-ui.card>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
