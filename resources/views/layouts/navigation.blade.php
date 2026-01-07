<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    @auth
                        <x-nav-link :href="route('weekly-overview.index')" :active="request()->routeIs('weekly-overview.*')">
                            {{ __('Przegląd Tygodniowy') }}
                        </x-nav-link>
                        
                        <!-- Zasoby Dropdown -->
                        <x-dropdown align="left" width="56">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150 {{ request()->routeIs('projects.*') || request()->routeIs('employees.*') || request()->routeIs('vehicles.*') || request()->routeIs('accommodations.*') || request()->routeIs('demands.*') ? 'text-gray-900' : '' }}">
                                    {{ __('Zasoby') }}
                                    <svg class="ms-1 -me-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <x-dropdown-link :href="route('projects.index')" :active="request()->routeIs('projects.*') || request()->routeIs('demands.*')">
                                    {{ __('Projekty') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('employees.index')" :active="request()->routeIs('employees.*')">
                                    {{ __('Pracownicy') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('vehicles.index')" :active="request()->routeIs('vehicles.*') || request()->routeIs('vehicle-assignments.*')">
                                    {{ __('Pojazdy') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('accommodations.index')" :active="request()->routeIs('accommodations.*') || request()->routeIs('accommodation-assignments.*')">
                                    {{ __('Mieszkania') }}
                                </x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                        
                        <x-nav-link :href="route('documents.index')" :active="request()->routeIs('documents.*')">
                            {{ __('Wymagania formalne') }}
                        </x-nav-link>
                        <x-nav-link :href="route('employee-documents.index')" :active="request()->routeIs('employee-documents.*') || request()->routeIs('employees.*employee-documents.*')">
                            {{ __('Dokumenty pracowników') }}
                        </x-nav-link>
                        <x-nav-link :href="route('locations.index')" :active="request()->routeIs('locations.*')">
                            {{ __('Lokalizacje') }}
                        </x-nav-link>
                        <x-nav-link :href="route('roles.index')" :active="request()->routeIs('roles.*')">
                            {{ __('Role') }}
                        </x-nav-link>
                        
                        <!-- Historia Przypisań Dropdown -->
                        <x-dropdown align="left" width="56">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150 {{ request()->routeIs('assignments.*') || request()->routeIs('vehicle-assignments.*') || request()->routeIs('accommodation-assignments.*') || request()->routeIs('demands.*') ? 'text-gray-900' : '' }}">
                                    {{ __('Historia przypisań') }}
                                    <svg class="ms-1 -me-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <x-dropdown-link :href="route('assignments.index')" :active="request()->routeIs('assignments.*')">
                                    {{ __('Przypisania pracowników') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('vehicle-assignments.index')" :active="request()->routeIs('vehicle-assignments.*')">
                                    {{ __('Przypisania pojazdów') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('accommodation-assignments.index')" :active="request()->routeIs('accommodation-assignments.*')">
                                    {{ __('Przypisania mieszkań') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('projects.index')" :active="request()->routeIs('projects.*') && request()->routeIs('*.demands.*')">
                                    {{ __('Zapotrzebowania projektów') }}
                                </x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                        
                        <!-- Administracja Dropdown -->
                        @canany(['users.viewAny', 'user-roles.viewAny'])
                        <x-dropdown align="left" width="56">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150 {{ request()->routeIs('users.*') || request()->routeIs('user-roles.*') ? 'text-gray-900' : '' }}">
                                    {{ __('Administracja') }}
                                    <svg class="ms-1 -me-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                @can('users.viewAny')
                                <x-dropdown-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                                    {{ __('Użytkownicy') }}
                                </x-dropdown-link>
                                @endcan
                                @can('user-roles.viewAny')
                                <x-dropdown-link :href="route('user-roles.index')" :active="request()->routeIs('user-roles.*')">
                                    {{ __('Role użytkowników') }}
                                </x-dropdown-link>
                                @endcan
                            </x-slot>
                        </x-dropdown>
                        @endcanany
                    @endauth
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @auth
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profil') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Wyloguj') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            @auth
                <x-responsive-nav-link :href="route('weekly-overview.index')" :active="request()->routeIs('weekly-overview.*')">
                    {{ __('Przegląd Tygodniowy') }}
                </x-responsive-nav-link>
                
                <div class="border-t border-gray-200 pt-2 mt-2">
                    <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        {{ __('Zasoby') }}
                    </div>
                    <x-responsive-nav-link :href="route('projects.index')" :active="request()->routeIs('projects.*') || request()->routeIs('demands.*')">
                        {{ __('Projekty') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('employees.index')" :active="request()->routeIs('employees.*')">
                        {{ __('Pracownicy') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('vehicles.index')" :active="request()->routeIs('vehicles.*') || request()->routeIs('vehicle-assignments.*')">
                        {{ __('Pojazdy') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('accommodations.index')" :active="request()->routeIs('accommodations.*') || request()->routeIs('accommodation-assignments.*')">
                        {{ __('Mieszkania') }}
                    </x-responsive-nav-link>
                </div>
                
                <x-responsive-nav-link :href="route('documents.index')" :active="request()->routeIs('documents.*')">
                    {{ __('Wymagania formalne') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('employee-documents.index')" :active="request()->routeIs('employee-documents.*') || request()->routeIs('employees.*employee-documents.*')">
                    {{ __('Dokumenty pracowników') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('locations.index')" :active="request()->routeIs('locations.*')">
                    {{ __('Lokalizacje') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('roles.index')" :active="request()->routeIs('roles.*')">
                    {{ __('Role') }}
                </x-responsive-nav-link>
                
                <div class="border-t border-gray-200 pt-2 mt-2">
                    <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        {{ __('Historia przypisań') }}
                    </div>
                    <x-responsive-nav-link :href="route('assignments.index')" :active="request()->routeIs('assignments.*')">
                        {{ __('Przypisania pracowników') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('vehicle-assignments.index')" :active="request()->routeIs('vehicle-assignments.*')">
                        {{ __('Przypisania pojazdów') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('accommodation-assignments.index')" :active="request()->routeIs('accommodation-assignments.*')">
                        {{ __('Przypisania mieszkań') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('projects.index')" :active="request()->routeIs('projects.*') && request()->routeIs('*.demands.*')">
                        {{ __('Zapotrzebowania projektów') }}
                    </x-responsive-nav-link>
                </div>
                
                @canany(['users.viewAny', 'user-roles.viewAny'])
                <div class="border-t border-gray-200 pt-2 mt-2">
                    <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        {{ __('Administracja') }}
                    </div>
                    @can('users.viewAny')
                    <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                        {{ __('Użytkownicy') }}
                    </x-responsive-nav-link>
                    @endcan
                    @can('user-roles.viewAny')
                    <x-responsive-nav-link :href="route('user-roles.index')" :active="request()->routeIs('user-roles.*')">
                        {{ __('Role użytkowników') }}
                    </x-responsive-nav-link>
                    @endcan
                </div>
                @endcanany
            @endauth
        </div>

        <!-- Responsive Settings Options -->
        @auth
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profil') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Wyloguj') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
        @endauth
    </div>
</nav>
