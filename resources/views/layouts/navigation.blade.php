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
            <ul class="navbar-nav me-auto">
                @auth
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('weekly-overview.*') ? 'active' : '' }}" href="{{ route('weekly-overview.index') }}">
                            <i class="bi bi-calendar-week"></i> Przegląd Tygodniowy
                        </a>
                    </li>

                    <!-- Zasoby Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('projects.*') || request()->routeIs('employees.*') || request()->routeIs('vehicles.*') || request()->routeIs('accommodations.*') ? 'active' : '' }}" href="#" id="zasobyDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-boxes"></i> Zasoby
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="zasobyDropdown">
                            <li><a class="dropdown-item {{ request()->routeIs('projects.*') ? 'active' : '' }}" href="{{ route('projects.index') }}"><i class="bi bi-folder"></i> Projekty</a></li>
                            <li><a class="dropdown-item {{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}"><i class="bi bi-people"></i> Pracownicy</a></li>
                            <li><a class="dropdown-item {{ request()->routeIs('vehicles.*') || request()->routeIs('vehicle-assignments.*') ? 'active' : '' }}" href="{{ route('vehicles.index') }}"><i class="bi bi-car-front"></i> Pojazdy</a></li>
                            <li><a class="dropdown-item {{ request()->routeIs('accommodations.*') || request()->routeIs('accommodation-assignments.*') ? 'active' : '' }}" href="{{ route('accommodations.index') }}"><i class="bi bi-house"></i> Mieszkania</a></li>
                        </ul>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('documents.*') ? 'active' : '' }}" href="{{ route('documents.index') }}">
                            <i class="bi bi-file-earmark-text"></i> Wymagania formalne
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('employee-documents.*') || request()->routeIs('employees.*employee-documents.*') ? 'active' : '' }}" href="{{ route('employee-documents.index') }}">
                            <i class="bi bi-file-earmark-medical"></i> Dokumenty pracowników
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('locations.*') ? 'active' : '' }}" href="{{ route('locations.index') }}">
                            <i class="bi bi-geo-alt"></i> Lokalizacje
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}">
                            <i class="bi bi-person-badge"></i> Role pracowników
                        </a>
                    </li>

                    <!-- Historia Przypisań Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('assignments.*') || request()->routeIs('vehicle-assignments.*') || request()->routeIs('accommodation-assignments.*') || request()->routeIs('demands.*') || request()->routeIs('return-trips.*') ? 'active' : '' }}" href="#" id="historiaDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-clock-history"></i> Historia przypisań
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="historiaDropdown">
                            <li><a class="dropdown-item {{ request()->routeIs('project-assignments.*') || request()->routeIs('assignments.*') ? 'active' : '' }}" href="{{ route('project-assignments.index') }}"><i class="bi bi-person-check"></i> Przypisania pracowników</a></li>
                            <li><a class="dropdown-item {{ request()->routeIs('vehicle-assignments.*') ? 'active' : '' }}" href="{{ route('vehicle-assignments.index') }}"><i class="bi bi-car-front-fill"></i> Przypisania pojazdów</a></li>
                            <li><a class="dropdown-item {{ request()->routeIs('accommodation-assignments.*') ? 'active' : '' }}" href="{{ route('accommodation-assignments.index') }}"><i class="bi bi-house-fill"></i> Przypisania mieszkań</a></li>
                            <li><a class="dropdown-item {{ request()->routeIs('project-demands.*') || request()->routeIs('demands.*') ? 'active' : '' }}" href="{{ route('project-demands.index') }}"><i class="bi bi-clipboard-data"></i> Zapotrzebowania projektów</a></li>
                            <li><a class="dropdown-item {{ request()->routeIs('return-trips.*') ? 'active' : '' }}" href="{{ route('return-trips.index') }}"><i class="bi bi-arrow-left-right"></i> Zjazdy</a></li>
                        </ul>
                    </li>

                    <!-- Logistyka Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('equipment.*') || request()->routeIs('equipment-issues.*') || request()->routeIs('transport-costs.*') || request()->routeIs('time-logs.*') ? 'active' : '' }}" href="#" id="logistykaDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-truck"></i> Logistyka
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="logistykaDropdown">
                            @can('viewAny', \App\Models\Equipment::class)
                                <li><a class="dropdown-item {{ request()->routeIs('equipment.*') ? 'active' : '' }}" href="{{ route('equipment.index') }}"><i class="bi bi-tools"></i> Sprzęt</a></li>
                            @endcan
                            @can('viewAny', \App\Models\EquipmentIssue::class)
                                <li><a class="dropdown-item {{ request()->routeIs('equipment-issues.*') ? 'active' : '' }}" href="{{ route('equipment-issues.index') }}"><i class="bi bi-box-arrow-up"></i> Wydania sprzętu</a></li>
                            @endcan
                            @can('viewAny', \App\Models\TransportCost::class)
                                <li><a class="dropdown-item {{ request()->routeIs('transport-costs.*') ? 'active' : '' }}" href="{{ route('transport-costs.index') }}"><i class="bi bi-currency-dollar"></i> Koszty transportu</a></li>
                            @endcan
                            @can('viewAny', \App\Models\TimeLog::class)
                                <li><a class="dropdown-item {{ request()->routeIs('time-logs.monthly-grid') ? 'active' : '' }}" href="{{ route('time-logs.monthly-grid') }}"><i class="bi bi-calendar-month"></i> Ewidencja godzin (grid)</a></li>
                                <li><a class="dropdown-item {{ request()->routeIs('time-logs.index') && !request()->routeIs('time-logs.monthly-grid') ? 'active' : '' }}" href="{{ route('time-logs.index') }}"><i class="bi bi-clock"></i> Lista wpisów</a></li>
                            @endcan
                        </ul>
                    </li>

                    <!-- Administracja Dropdown -->
                    @canany(['users.viewAny', 'user-roles.viewAny'])
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('users.*') || request()->routeIs('user-roles.*') ? 'active' : '' }}" href="#" id="administracjaDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-shield-lock"></i> Administracja
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="administracjaDropdown">
                            @can('users.viewAny')
                                <li><a class="dropdown-item {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><i class="bi bi-person-gear"></i> Użytkownicy</a></li>
                            @endcan
                            @can('user-roles.viewAny')
                                <li><a class="dropdown-item {{ request()->routeIs('user-roles.*') ? 'active' : '' }}" href="{{ route('user-roles.index') }}"><i class="bi bi-shield-check"></i> Role użytkowników</a></li>
                            @endcan
                        </ul>
                    </li>
                    @endcanany

                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('utest') ? 'active' : '' }}" href="{{ url('/utest') }}">
                            <i class="bi bi-palette"></i> UI Test
                        </a>
                    </li>
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
