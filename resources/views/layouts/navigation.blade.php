<nav class="navbar navbar-expand-lg navbar-light" @if(config('app.env') !== 'production') style="background: #000000 !important;" @endif>
    <div class="container-fluid">
        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
            <x-application-logo class="d-block navbar-logo" />
            <span class="navbar-brand-name">Chrono<span class="navbar-brand-name__accent">Logic</span></span>
            @if(config('app.env') !== 'production')
                <span class="badge bg-danger-white fw-semibold">
                    {{ strtoupper(config('app.env')) }}
                </span>
            @endif
        </a>

        {{-- Dzwonek + hamburger zawsze widoczne na mobile (poza collapse) --}}
        <div class="d-flex align-items-center gap-1 ms-auto order-lg-last">
            @auth
                @livewire('notification-bell')
            @endauth
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <!-- Navigation content -->
        <div class="collapse navbar-collapse flex-lg-grow-1" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                @auth
                    <x-nav.menu />
                @endauth
            </ul>

            <!-- Right side: User menu -->
            <ul class="navbar-nav align-items-center gap-1">
                @auth
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
