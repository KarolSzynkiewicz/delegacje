<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container-fluid">
        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center" href="{{ route('dashboard') }}">
            <x-application-logo class="d-block navbar-logo" />
        </a>

        <!-- Toggle button for mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation content -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                @auth
                    <x-nav.link 
                        route="profitability.index" 
                        routePattern="profitability.*"
                        icon="bi bi-graph-up-arrow"
                        permission="profitability.view"
                    >
                        Dashboard
                    </x-nav.link>

                    <x-nav.link 
                        route="weekly-overview.index" 
                        routePattern="weekly-overview.*"
                        icon="bi bi-calendar-week"
                        permission="weekly-overview.view"
                    >
                        Przegląd Tygodniowy
                    </x-nav.link>

                    <!-- Zasoby Dropdown -->
                    @php
                        $zasobyPatterns = ['projects.*', 'vehicles.*', 'accommodations.*', 'locations.*', 'vehicle-assignments.*', 'accommodation-assignments.*'];
                    @endphp
                    <x-nav.dropdown 
                        label="Zasoby" 
                        icon="bi bi-boxes"
                        :routePatterns="$zasobyPatterns"
                    >
                        <x-nav.item 
                            route="projects.index" 
                            routePattern="projects.*"
                            icon="bi bi-folder"
                            permission="projects.view"
                        >
                            Projekty
                        </x-nav.item>
                        @php
                            $vehiclesPatterns = ['vehicles.*', 'vehicle-assignments.*'];
                            $accommodationsPatterns = ['accommodations.*', 'accommodation-assignments.*'];
                        @endphp
                        <x-nav.item 
                            route="vehicles.index" 
                            :routePattern="$vehiclesPatterns"
                            icon="bi bi-car-front"
                            permission="vehicles.view"
                        >
                            Pojazdy
                        </x-nav.item>
                        <x-nav.item 
                            route="accommodations.index" 
                            :routePattern="$accommodationsPatterns"
                            icon="bi bi-house"
                            permission="accommodations.view"
                        >
                            Mieszkania
                        </x-nav.item>
                        <x-nav.item 
                            route="locations.index" 
                            routePattern="locations.*"
                            icon="bi bi-geo-alt"
                            permission="locations.view"
                        >
                            Lokalizacje
                        </x-nav.item>
                    </x-nav.dropdown>

                    <!-- Przypisania Dropdown -->
                    @php
                        $przypisaniaPatterns = ['assignments.*', 'vehicle-assignments.*', 'accommodation-assignments.*', 'demands.*', 'return-trips.*', 'project-assignments.*', 'project-demands.*'];
                        $assignmentsPatterns = ['project-assignments.*', 'assignments.*'];
                        $demandsPatterns = ['project-demands.*', 'demands.*'];
                        $employeeDocsPatterns = ['employee-documents.*', 'employees.*employee-documents.*'];
                    @endphp
                    <x-nav.dropdown 
                        label="Przypisania" 
                        icon="bi bi-clock-history"
                        :routePatterns="$przypisaniaPatterns"
                    >
                        <x-nav.item 
                            route="project-assignments.index" 
                            :routePattern="$assignmentsPatterns"
                            icon="bi bi-person-check"
                            permission="assignments.view"
                        >
                            Przypisania pracowników
                        </x-nav.item>
                        <x-nav.item 
                            route="vehicle-assignments.index" 
                            routePattern="vehicle-assignments.*"
                            icon="bi bi-car-front-fill"
                            permission="vehicle-assignments.view"
                        >
                            Przypisania pojazdów
                        </x-nav.item>
                        <x-nav.item 
                            route="accommodation-assignments.index" 
                            routePattern="accommodation-assignments.*"
                            icon="bi bi-house-fill"
                            permission="accommodation-assignments.view"
                        >
                            Przypisania mieszkań
                        </x-nav.item>
                        <x-nav.item 
                            route="project-demands.index" 
                            :routePattern="$demandsPatterns"
                            icon="bi bi-clipboard-data"
                            permission="demands.view"
                        >
                            Zapotrzebowania projektów
                        </x-nav.item>
                        <x-nav.item 
                            route="return-trips.index" 
                            routePattern="return-trips.*"
                            icon="bi bi-arrow-left-right"
                            permission="return-trips.view"
                        >
                            Zjazdy
                        </x-nav.item>
                    </x-nav.dropdown>

                    <!-- Logistyka Dropdown -->
                    @php
                        $logistykaPatterns = ['equipment.*', 'equipment-issues.*'];
                    @endphp
                    <x-nav.dropdown 
                        label="Logistyka" 
                        icon="bi bi-truck"
                        :routePatterns="$logistykaPatterns"
                    >
                        <x-nav.item 
                            route="equipment.index" 
                            routePattern="equipment.*"
                            icon="bi bi-tools"
                            permission="equipment.view"
                        >
                            Sprzęt
                        </x-nav.item>
                        <x-nav.item 
                            route="equipment-issues.index" 
                            routePattern="equipment-issues.*"
                            icon="bi bi-box-arrow-up"
                            permission="equipment-issues.view"
                        >
                            Wydania sprzętu
                        </x-nav.item>
                    </x-nav.dropdown>

                    <!-- Koszty Dropdown -->
                    @php
                        $kosztyPatterns = ['project-variable-costs.*', 'transport-costs.*', 'fixed-costs.*'];
                    @endphp
                    <x-nav.dropdown 
                        label="Koszty" 
                        icon="bi bi-cash-stack"
                        :routePatterns="$kosztyPatterns"
                    >
                        <x-nav.item 
                            route="project-variable-costs.index" 
                            routePattern="project-variable-costs.*"
                            icon="bi bi-arrow-repeat"
                            permission="project-variable-costs.view"
                        >
                            Koszty zmienne
                        </x-nav.item>
                        <x-nav.item 
                            route="transport-costs.index" 
                            routePattern="transport-costs.*"
                            icon="bi bi-truck"
                            permission="transport-costs.view"
                        >
                            Koszty transportu
                        </x-nav.item>
                        <x-nav.item 
                            route="fixed-costs.index" 
                            routePattern="fixed-costs.*"
                            icon="bi bi-lock"
                            permission="fixed-costs.view"
                        >
                            Koszty stałe
                        </x-nav.item>
                    </x-nav.dropdown>

                    <!-- Kadry Dropdown -->
                    @php
                        $kadryPatterns = ['employees.*', 'roles.*', 'adjustments.*', 'time-logs.*', 'payrolls.*', 'rotations.*', 'documents.*', 'employee-documents.*', 'employee-rates.*', 'advances.*'];
                    @endphp
                    <x-nav.dropdown 
                        label="Kadry" 
                        icon="bi bi-briefcase"
                        :routePatterns="$kadryPatterns"
                    >
                        <x-nav.item 
                            route="employees.index" 
                            routePattern="employees.*"
                            icon="bi bi-people"
                            permission="employees.view"
                        >
                            Pracownicy
                        </x-nav.item>
                        <x-nav.item 
                            route="roles.index" 
                            routePattern="roles.*"
                            icon="bi bi-person-badge"
                            permission="roles.view"
                        >
                            Role pracowników
                        </x-nav.item>
                        <x-nav.item 
                            route="adjustments.index" 
                            routePattern="adjustments.*"
                            icon="bi bi-award"
                            permission="adjustments.view"
                        >
                            Kary i nagrody
                        </x-nav.item>
                        <x-nav.item 
                            route="time-logs.index" 
                            routePattern="time-logs.*"
                            icon="bi bi-clock"
                            permission="time-logs.view"
                        >
                            Ewidencje godzin
                        </x-nav.item>
                        <x-nav.item 
                            route="payrolls.index" 
                            routePattern="payrolls.*"
                            icon="bi bi-cash-stack"
                            permission="payrolls.view"
                        >
                            Payroll
                        </x-nav.item>
                        <x-nav.item 
                            route="rotations.index" 
                            routePattern="rotations.*"
                            icon="bi bi-arrow-repeat"
                            permission="rotations.view"
                        >
                            Rotacje
                        </x-nav.item>
                        <x-nav.item 
                            route="employee-rates.index" 
                            routePattern="employee-rates.*"
                            icon="bi bi-currency-dollar"
                            permission="employee-rates.view"
                        >
                            Stawki pracowników
                        </x-nav.item>
                        <x-nav.item 
                            route="advances.index" 
                            routePattern="advances.*"
                            icon="bi bi-wallet2"
                            permission="advances.view"
                        >
                            Zaliczki
                        </x-nav.item>
                        <x-nav.item 
                            route="documents.index" 
                            routePattern="documents.*"
                            icon="bi bi-file-earmark-text"
                            permission="documents.view"
                        >
                            Wymagania formalne
                        </x-nav.item>
                        <x-nav.item 
                            route="employee-documents.index" 
                            :routePattern="$employeeDocsPatterns"
                            icon="bi bi-file-earmark-medical"
                            permission="employee-documents.view"
                        >
                            Dokumenty pracowników
                        </x-nav.item>
                    </x-nav.dropdown>

                    <!-- Administracja Dropdown -->
                    @php
                        $adminPatterns = ['users.*', 'user-roles.*'];
                    @endphp
                    <x-nav.dropdown 
                        label="Administracja" 
                        icon="bi bi-shield-lock"
                        :routePatterns="$adminPatterns"
                    >
                        <x-nav.item 
                            route="users.index" 
                            routePattern="users.*"
                            icon="bi bi-person-gear"
                            permission="users.view"
                        >
                            Użytkownicy
                        </x-nav.item>
                        <x-nav.item 
                            route="user-roles.index" 
                            routePattern="user-roles.*"
                            icon="bi bi-shield-check"
                            permission="user-roles.view"
                        >
                            Role użytkowników
                        </x-nav.item>
                    </x-nav.dropdown>
                @endauth
            </ul>

            <!-- Right side: User menu -->
            <ul class="navbar-nav">
                @auth
                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            @if(Auth::user()->image_path)
                                <img src="{{ Auth::user()->image_url }}" alt="{{ Auth::user()->name }}" class="rounded-circle me-2 user-avatar">
                            @else
                                <div class="bg-primary bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center me-2 user-avatar-placeholder">
                                    <span class="text-primary fw-semibold small">{{ Auth::user()->initials }}</span>
                                </div>
                            @endif
                            <span>{{ Auth::user()->name }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person"></i> Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="bi bi-box-arrow-right"></i> Wyloguj
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
