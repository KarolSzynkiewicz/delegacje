<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Wiersz 1: 3 kolumny -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="row align-items-start g-4">
                    <!-- Kolumna 1: Zdjęcie -->
                    <div class="col-auto">
                        @php
                            $nameParts = explode(' ', $user->name);
                            $initials = count($nameParts) >= 2 
                                ? strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1))
                                : strtoupper(substr($user->name, 0, 1));
                            $imageUrl = $user->image_path ? $user->image_url : null;
                        @endphp
                        <x-ui.avatar 
                            :image-url="$imageUrl"
                            :alt="$user->name"
                            :initials="$initials"
                            size="120px"
                            shape="circle"
                        />
                    </div>
                    
                    <!-- Kolumna 2: Username, Email, Wybierz plik, Zapisz zdjęcie -->
                    <div class="col">
                        <div class="d-flex flex-column gap-3">
                            <div>
                                <h3 class="fs-5 fw-semibold mb-0">{{ $user->name }}</h3>
                            </div>
                            <div>
                                <span class="text-muted">
                                    <i class="bi bi-envelope me-1"></i>
                                    {{ $user->email }}
                                </span>
                            </div>
                            
                            <!-- Update Photo -->
                            <div class="mt-2">
                                <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="d-inline">
                                    @csrf
                                    @method('patch')
                                    <input type="hidden" name="name" value="{{ $user->name }}" />
                                    <input type="hidden" name="email" value="{{ $user->email }}" />
                                    
                                    <div class="mb-2">
                                        <x-ui.image-preview 
                                            :showCurrentImage="false"
                                            :currentImageUrl="null"
                                            :currentImage="null"
                                        />
                                        <x-input-error class="mt-2" :messages="$errors->get('image')" />
                                    </div>
                                    
                                    <div class="d-flex align-items-center gap-2">
                                        <x-primary-button class="btn-sm">{{ __('Zapisz zdjęcie') }}</x-primary-button>
                                        @if (session('status') === 'profile-updated')
                                            <span class="text-success small">{{ __('Zapisano') }}</span>
                                        @endif
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Kolumna 3: Rola i Kierownik -->
                    <div class="col">
                        <div class="d-flex flex-column gap-3">
                            <!-- Rola -->
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="fw-semibold">Rola</span>
                                    <x-tooltip title="Rola zapewnia dostęp do modułów zgodnie z konfiguracją uprawnień. Uprawnienia przypisane do roli określają, do jakich funkcji i danych użytkownik ma dostęp w systemie." direction="bottom">
                                        <i class="bi bi-info-circle text-primary fs-6"></i>
                                    </x-tooltip>
                                </div>
                                @if($user->roles->count() > 0)
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($user->roles as $role)
                                            <x-ui.badge variant="info">{{ $role->name }}</x-ui.badge>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-muted small mb-0">Brak przypisanych ról</p>
                                @endif
                            </div>
                            
                            <!-- Kierownik -->
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="fw-semibold">Kierownik</span>
                                    <x-tooltip title="Kierownictwo projektów zapewnia dostęp do zakładki 'Mój zespół' dla projektów, którymi użytkownik zarządza. Jako kierownik możesz przeglądać i zarządzać danymi pracowników przypisanych do Twoich projektów, dodawać oceny, zarządzać zadaniami i ewidencją godzin." direction="bottom">
                                        <i class="bi bi-info-circle text-primary fs-6"></i>
                                    </x-tooltip>
                                </div>
                                @if($user->managedProjects->count() > 0)
                                    <div class="d-flex flex-column gap-1">
                                        <span class="text-muted small">{{ $user->managedProjects->count() }} {{ $user->managedProjects->count() === 1 ? 'projekt' : 'projektów' }}</span>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($user->managedProjects->take(3) as $project)
                                                <x-ui.badge variant="primary">
                                                    <i class="bi bi-folder me-1"></i>{{ $project->name }}
                                                </x-ui.badge>
                                            @endforeach
                                            @if($user->managedProjects->count() > 3)
                                                <x-ui.badge variant="secondary">
                                                    +{{ $user->managedProjects->count() - 3 }} więcej
                                                </x-ui.badge>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <p class="text-muted small mb-0">Nie zarządzasz żadnymi projektami</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Wiersz 2: 2 kolumny -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="row g-4">
                    <!-- Kolumna 1: Obecne hasło (formularz zmiany hasła) -->
                    <div class="col-md-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-3">{{ __('Zmień hasło') }}</h3>
                        <form method="post" action="{{ route('password.update') }}">
                            @csrf
                            @method('put')
                            
                            <div class="mb-3">
                                <x-input-label for="update_password_current_password" :value="__('Obecne hasło')" />
                                <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
                                <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
                            </div>
                            
                            <div class="mb-3">
                                <x-input-label for="update_password_password" :value="__('Nowe hasło')" />
                                <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
                            </div>
                            
                            <div class="mb-3">
                                <x-input-label for="update_password_password_confirmation" :value="__('Potwierdź hasło')" />
                                <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
                            </div>
                            
                            <div class="d-flex align-items-center gap-2">
                                <x-primary-button>{{ __('Zapisz hasło') }}</x-primary-button>
                                @if (session('status') === 'password-updated')
                                    <span class="text-success small">{{ __('Zapisano') }}</span>
                                @endif
                            </div>
                        </form>
                    </div>
                    
                    <!-- Kolumna 2: Usuń konto -->
                    <div class="col-md-6">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
