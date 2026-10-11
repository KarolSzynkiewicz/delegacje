@php
    $nameParts = preg_split('/\s+/', trim($user->name)) ?: [];
    $initials = count($nameParts) >= 2
        ? strtoupper(substr($nameParts[0], 0, 1).substr($nameParts[1], 0, 1))
        : strtoupper(substr($user->name, 0, 1));
    $imageUrl = $user->image_path ? $user->image_url : null;
    $managed = $user->managedProjects;
    $managedCount = $managed->count();
    $lead = $managedCount === 0
        ? 'Kierownik · nie zarządzasz żadnymi projektami'
        : 'Kierownik · '.$managed->take(3)->pluck('name')->join(', ').($managedCount > 3 ? ' +'.($managedCount - 3) : '');
    $otherSessions = collect($sessions)->contains(fn (array $session) => ! $session['current'] && $session['id']);
@endphp

<x-app-layout>
    <div class="profile-page d-flex flex-column gap-3">
        <x-ui.card>
            <div class="profile-hero">
                <div class="profile-hero__avatar">
                    <x-ui.avatar
                        :image-url="$imageUrl"
                        :alt="$user->name"
                        :initials="$initials"
                        size="88px"
                        shape="circle"
                    />
                    <label for="profile-image" class="profile-hero__cam" title="Zmień zdjęcie">
                        <i class="bi bi-camera"></i>
                    </label>
                </div>
                <div class="profile-hero__identity">
                    <h2 class="profile-hero__name">{{ $user->name }}</h2>
                    <div class="profile-hero__email">
                        <i class="bi bi-envelope"></i>
                        <span>{{ $user->email }}</span>
                    </div>
                    @if($user->roles->isNotEmpty())
                        <div class="profile-hero__roles">
                            @foreach($user->roles as $role)
                                <x-ui.badge variant="info">{{ $role->name }}</x-ui.badge>
                            @endforeach
                        </div>
                    @endif
                    <p class="profile-hero__lead mb-0">{{ $lead }}</p>
                </div>
            </div>
        </x-ui.card>

        <div class="row g-3">
            <div class="col-lg-6">
                <x-ui.card class="h-100">
                    <form
                        method="post"
                        action="{{ route('profile.update') }}"
                        enctype="multipart/form-data"
                        class="profile-card"
                        x-data="{ fileName: '', over: false }"
                    >
                        @csrf
                        @method('patch')
                        <input type="hidden" name="email" value="{{ $user->email }}">

                        <h3 class="profile-card__title">Dane użytkownika</h3>

                        <div class="mb-3">
                            <label class="form-label" for="profile-name">Nazwa użytkownika <span class="text-danger">*</span></label>
                            <input
                                id="profile-name"
                                name="name"
                                type="text"
                                class="form-control"
                                value="{{ old('name', $user->name) }}"
                                required
                            >
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <label
                            for="profile-image"
                            class="profile-drop"
                            :class="over && 'is-over'"
                            @dragover.prevent="over = true"
                            @dragleave.prevent="over = false"
                            @drop.prevent="
                                over = false;
                                const input = $refs.file;
                                if ($event.dataTransfer.files.length) {
                                    input.files = $event.dataTransfer.files;
                                    fileName = input.files[0]?.name || '';
                                }
                            "
                        >
                            <span x-text="fileName || 'Przeciągnij zdjęcie lub wybierz plik'"></span>
                            <small>Maks. 2 MB · JPEG, PNG, GIF, WEBP</small>
                        </label>
                        <input
                            x-ref="file"
                            id="profile-image"
                            name="image"
                            type="file"
                            class="visually-hidden"
                            accept="image/jpeg,image/png,image/gif,image/webp"
                            @change="fileName = $event.target.files[0]?.name || ''"
                        >
                        <x-input-error class="mt-2" :messages="$errors->get('image')" />

                        <div class="profile-card__actions">
                            @if (session('status') === 'profile-updated')
                                <span class="text-success small">Zapisano</span>
                            @endif
                            <button type="submit" class="btn btn-primary">Zapisz zmiany</button>
                        </div>
                    </form>
                </x-ui.card>
            </div>

            <div class="col-lg-6">
                <x-ui.card class="h-100">
                    <form method="post" action="{{ route('password.update') }}" class="profile-card">
                        @csrf
                        @method('put')

                        <h3 class="profile-card__title">Zmień hasło</h3>

                        <div class="mb-3">
                            <label class="form-label" for="update_password_current_password">Obecne hasło</label>
                            <div class="profile-secret" x-data="{ show: false }">
                                <input
                                    id="update_password_current_password"
                                    name="current_password"
                                    :type="show ? 'text' : 'password'"
                                    class="form-control"
                                    autocomplete="current-password"
                                >
                                <button type="button" class="profile-secret__eye" @click="show = !show" :aria-label="show ? 'Ukryj hasło' : 'Pokaż hasło'">
                                    <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'"></i>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="update_password_password">Nowe hasło</label>
                            <div class="profile-secret" x-data="{ show: false }">
                                <input
                                    id="update_password_password"
                                    name="password"
                                    :type="show ? 'text' : 'password'"
                                    class="form-control"
                                    autocomplete="new-password"
                                >
                                <button type="button" class="profile-secret__eye" @click="show = !show" :aria-label="show ? 'Ukryj hasło' : 'Pokaż hasło'">
                                    <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'"></i>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="update_password_password_confirmation">Potwierdź hasło</label>
                            <div class="profile-secret" x-data="{ show: false }">
                                <input
                                    id="update_password_password_confirmation"
                                    name="password_confirmation"
                                    :type="show ? 'text' : 'password'"
                                    class="form-control"
                                    autocomplete="new-password"
                                >
                                <button type="button" class="profile-secret__eye" @click="show = !show" :aria-label="show ? 'Ukryj hasło' : 'Pokaż hasło'">
                                    <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'"></i>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
                        </div>

                        <div class="profile-card__actions">
                            @if (session('status') === 'password-updated')
                                <span class="text-success small">Zapisano</span>
                            @endif
                            <button type="submit" class="btn btn-primary">Zapisz hasło</button>
                        </div>
                    </form>
                </x-ui.card>
            </div>
        </div>

        @include('profile.partials.delete-user-form')

        <x-ui.card>
            <div class="profile-card">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <h3 class="profile-card__title mb-1">Sesje i urządzenia</h3>
                        <p class="text-muted small mb-0">Gdzie jesteś zalogowany i które przeglądarki dostają push.</p>
                    </div>
                    @if($otherSessions)
                        <form method="post" action="{{ route('profile.sessions.destroy-others') }}">
                            @csrf
                            @method('delete')
                            <button type="submit" class="btn btn-sm btn-outline-secondary">Wyloguj pozostałe</button>
                        </form>
                    @endif
                </div>

                <div class="profile-list-label">Sesje</div>
                <div class="profile-list">
                    @foreach($sessions as $session)
                        <div class="profile-list__row">
                            <i class="bi {{ $session['icon'] }} profile-list__icon"></i>
                            <div class="profile-list__body">
                                <div class="profile-list__title">
                                    {{ $session['label'] }}
                                    @if($session['current'])
                                        <span class="profile-list__badge">ta sesja</span>
                                    @endif
                                </div>
                                <div class="profile-list__meta">{{ $session['ip'] }} · {{ $session['seen'] }}</div>
                            </div>
                            @if($session['id'] && ! $session['current'])
                                <form method="post" action="{{ route('profile.sessions.destroy', $session['id']) }}">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Wyloguj</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="profile-list-label">Push</div>
                @if($pushDevices === [])
                    <p class="text-muted small mb-0">Żadna przeglądarka nie dostaje jeszcze push.</p>
                @else
                    <div class="profile-list">
                        @foreach($pushDevices as $device)
                            <div class="profile-list__row">
                                <i class="bi {{ $device['icon'] }} profile-list__icon"></i>
                                <div class="profile-list__body">
                                    <div class="profile-list__title">{{ $device['label'] }}</div>
                                    @if($device['seen'] !== '')
                                        <div class="profile-list__meta">{{ $device['seen'] }}</div>
                                    @endif
                                </div>
                                <form method="post" action="{{ route('profile.devices.destroy', $device['id']) }}">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Odłącz</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-ui.card>

        <livewire:notification-preferences />
    </div>
</x-app-layout>
