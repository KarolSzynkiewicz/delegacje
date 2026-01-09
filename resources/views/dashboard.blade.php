<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-semibold fs-4 text-dark mb-0">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h3 class="fs-5 fw-semibold mb-3">Witaj w systemie zarządzania logistyką!</h3>
                    <p class="mb-0">System Stocznia - zarządzanie projektami, pracownikami i zasobami.</p>
                </div>
            </div>
            
            <div class="row g-4">
                <!-- Widok Tygodniowy -->
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('weekly-overview.index') }}" class="card text-decoration-none h-100 shadow-sm border-0 hover-shadow">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-calendar-week text-info fs-3 me-2"></i>
                                <h4 class="card-title mb-0 text-info">Przegląd Tygodniowy</h4>
                            </div>
                            <p class="card-text small text-muted mb-2">Tygodniowy podgląd przydziałów ekip</p>
                            <p class="card-text small text-muted mb-0">Projekty • Pracownicy • Domy • Auta</p>
                        </div>
                    </a>
                </div>

                <!-- Projekty -->
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('projects.index') }}" class="card text-decoration-none h-100 shadow-sm border-0 hover-shadow">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-folder text-primary fs-3 me-2"></i>
                                <h4 class="card-title mb-0 text-primary">Projekty</h4>
                            </div>
                            <p class="card-text small text-muted mb-0">Zarządzaj projektami i zapotrzebowaniem</p>
                        </div>
                    </a>
                </div>

                <!-- Przypisania -->
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('project-assignments.index') }}" class="card text-decoration-none h-100 shadow-sm border-0 hover-shadow">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-person-check text-success fs-3 me-2"></i>
                                <h4 class="card-title mb-0 text-success">Przypisania</h4>
                            </div>
                            <p class="card-text small text-muted mb-0">Przypisania pracowników do projektów</p>
                        </div>
                    </a>
                </div>

                <!-- Pracownicy -->
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('employees.index') }}" class="card text-decoration-none h-100 shadow-sm border-0 hover-shadow">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-people text-purple fs-3 me-2"></i>
                                <h4 class="card-title mb-0 text-purple">Pracownicy</h4>
                            </div>
                            <p class="card-text small text-muted mb-0">Zarządzaj bazą pracowników</p>
                        </div>
                    </a>
                </div>

                <!-- Rotacje Pracowników -->
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('rotations.index') }}" class="card text-decoration-none h-100 shadow-sm border-0 hover-shadow">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-arrow-repeat text-info fs-3 me-2"></i>
                                <h4 class="card-title mb-0 text-info">Rotacje Pracowników</h4>
                            </div>
                            <p class="card-text small text-muted mb-0">Zarządzaj rotacjami dostępności</p>
                        </div>
                    </a>
                </div>

                <!-- Pojazdy -->
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('vehicles.index') }}" class="card text-decoration-none h-100 shadow-sm border-0 hover-shadow">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-car-front text-warning fs-3 me-2"></i>
                                <h4 class="card-title mb-0 text-warning">Pojazdy</h4>
                            </div>
                            <p class="card-text small text-muted mb-0">Zarządzaj flotą pojazdów</p>
                        </div>
                    </a>
                </div>

                <!-- Mieszkania -->
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('accommodations.index') }}" class="card text-decoration-none h-100 shadow-sm border-0 hover-shadow">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-house text-danger fs-3 me-2"></i>
                                <h4 class="card-title mb-0 text-danger">Mieszkania</h4>
                            </div>
                            <p class="card-text small text-muted mb-0">Zarządzaj akomodacjami</p>
                        </div>
                    </a>
                </div>

                <!-- Przypisania Pojazdów -->
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('vehicle-assignments.index') }}" class="card text-decoration-none h-100 shadow-sm border-0 hover-shadow">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-car-front-fill text-primary fs-3 me-2"></i>
                                <h4 class="card-title mb-0 text-primary">Przypisania Pojazdów</h4>
                            </div>
                            <p class="card-text small text-muted mb-0">Przypisania pracownik-pojazd</p>
                        </div>
                    </a>
                </div>

                <!-- Przypisania Mieszkań -->
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('accommodation-assignments.index') }}" class="card text-decoration-none h-100 shadow-sm border-0 hover-shadow">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-house-fill text-danger fs-3 me-2"></i>
                                <h4 class="card-title mb-0 text-danger">Przypisania Mieszkań</h4>
                            </div>
                            <p class="card-text small text-muted mb-0">Przypisania pracownik-mieszkanie</p>
                        </div>
                    </a>
                </div>

                <!-- Lokalizacje -->
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('locations.index') }}" class="card text-decoration-none h-100 shadow-sm border-0 hover-shadow">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-geo-alt text-success fs-3 me-2"></i>
                                <h4 class="card-title mb-0 text-success">Lokalizacje</h4>
                            </div>
                            <p class="card-text small text-muted mb-0">Zarządzaj lokalizacjami projektów</p>
                        </div>
                    </a>
                </div>

                <!-- Role -->
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('roles.index') }}" class="card text-decoration-none h-100 shadow-sm border-0 hover-shadow">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-person-badge text-warning fs-3 me-2"></i>
                                <h4 class="card-title mb-0 text-warning">Role</h4>
                            </div>
                            <p class="card-text small text-muted mb-0">Zarządzaj rolami w projektach</p>
                        </div>
                    </a>
                </div>

                <!-- Użytkownicy -->
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('users.index') }}" class="card text-decoration-none h-100 shadow-sm border-0 hover-shadow">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-person-gear text-primary fs-3 me-2"></i>
                                <h4 class="card-title mb-0 text-primary">Użytkownicy</h4>
                            </div>
                            <p class="card-text small text-muted mb-0">Zarządzaj użytkownikami systemu</p>
                        </div>
                    </a>
                </div>

                <!-- Role Użytkowników -->
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('user-roles.index') }}" class="card text-decoration-none h-100 shadow-sm border-0 hover-shadow">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-shield-check text-primary fs-3 me-2"></i>
                                <h4 class="card-title mb-0 text-primary">Role Użytkowników</h4>
                            </div>
                            <p class="card-text small text-muted mb-0">Zarządzaj rolami i uprawnieniami</p>
                        </div>
                    </a>
                </div>

                <!-- Wymagania formalne -->
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('documents.index') }}" class="card text-decoration-none h-100 shadow-sm border-0 hover-shadow">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-file-earmark-text text-primary fs-3 me-2"></i>
                                <h4 class="card-title mb-0 text-primary">Wymagania formalne</h4>
                            </div>
                            <p class="card-text small text-muted mb-0">Zarządzaj wymaganiami formalnymi</p>
                        </div>
                    </a>
                </div>

                <!-- Dokumenty Pracowników -->
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('employee-documents.index') }}" class="card text-decoration-none h-100 shadow-sm border-0 hover-shadow">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-file-earmark-medical text-warning fs-3 me-2"></i>
                                <h4 class="card-title mb-0 text-warning">Dokumenty Pracowników</h4>
                            </div>
                            <p class="card-text small text-muted mb-0">Zarządzaj dokumentami pracowników</p>
                        </div>
                    </a>
                </div>

                <!-- Zjazdy -->
                @can('viewAny', \App\Models\LogisticsEvent::class)
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('return-trips.index') }}" class="card text-decoration-none h-100 shadow-sm border-0 hover-shadow">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-arrow-left-right text-secondary fs-3 me-2"></i>
                                <h4 class="card-title mb-0 text-secondary">Zjazdy</h4>
                            </div>
                            <p class="card-text small text-muted mb-0">Zarządzaj zjazdami pracowników do bazy</p>
                        </div>
                    </a>
                </div>
                @endcan

                <!-- Sprzęt -->
                @can('viewAny', \App\Models\Equipment::class)
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('equipment.index') }}" class="card text-decoration-none h-100 shadow-sm border-0 hover-shadow">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-tools text-success fs-3 me-2"></i>
                                <h4 class="card-title mb-0 text-success">Sprzęt</h4>
                            </div>
                            <p class="card-text small text-muted mb-0">Zarządzaj sprzętem i magazynem</p>
                        </div>
                    </a>
                </div>
                @endcan

                <!-- Wydania Sprzętu -->
                @can('viewAny', \App\Models\EquipmentIssue::class)
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('equipment-issues.index') }}" class="card text-decoration-none h-100 shadow-sm border-0 hover-shadow">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-box-arrow-up text-success fs-3 me-2"></i>
                                <h4 class="card-title mb-0 text-success">Wydania Sprzętu</h4>
                            </div>
                            <p class="card-text small text-muted mb-0">Wydania i zwroty sprzętu</p>
                        </div>
                    </a>
                </div>
                @endcan

                <!-- Koszty Transportu -->
                @can('viewAny', \App\Models\TransportCost::class)
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('transport-costs.index') }}" class="card text-decoration-none h-100 shadow-sm border-0 hover-shadow">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-currency-dollar text-danger fs-3 me-2"></i>
                                <h4 class="card-title mb-0 text-danger">Koszty Transportu</h4>
                            </div>
                            <p class="card-text small text-muted mb-0">Ewidencja kosztów transportu</p>
                        </div>
                    </a>
                </div>
                @endcan

                <!-- Ewidencja Godzin -->
                @can('viewAny', \App\Models\TimeLog::class)
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('time-logs.index') }}" class="card text-decoration-none h-100 shadow-sm border-0 hover-shadow">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-clock text-info fs-3 me-2"></i>
                                <h4 class="card-title mb-0 text-info">Ewidencja Godzin</h4>
                            </div>
                            <p class="card-text small text-muted mb-0">Rejestracja rzeczywistych godzin pracy</p>
                        </div>
                    </a>
                </div>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>

<style>
    .hover-shadow {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .hover-shadow:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    
    .text-purple {
        color: #6f42c1 !important;
    }
</style>
