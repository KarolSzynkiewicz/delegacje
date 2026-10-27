@php
    $office = $user->officeProfile;
    $officeActive = $office && $office->is_active
        && ($office->ended_on === null || $office->ended_on->gte(now()->startOfDay()));
    $directPermissionIds = $user->permissions->pluck('id');
    $permissionTotal = $permissionGroups->sum('count');
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Użytkownik">
            <x-slot name="left">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('users.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('users.edit', $user) }}"
                    routeName="users.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if (session('success'))
        <x-alert type="success" dismissible icon="check-circle">
            {{ session('success') }}
        </x-alert>
    @endif

    <div class="user-show profile-page d-flex flex-column gap-3">
        {{-- Hero: osoba + zdjęcie --}}
        <x-ui.card>
            <div class="profile-hero user-show__hero">
                <div class="profile-hero__avatar">
                    <x-ui.avatar
                        :image-url="$user->image_url"
                        :alt="$user->name"
                        :initials="$user->initials"
                        size="96px"
                        shape="circle"
                    />
                </div>
                <div class="profile-hero__identity">
                    <h2 class="profile-hero__name">{{ $user->name }}</h2>
                    <div class="profile-hero__email">
                        <i class="bi bi-envelope"></i>
                        <span>{{ $user->email }}</span>
                    </div>
                    <div class="user-show__chips">
                        @if($user->isAdmin())
                            <x-ui.badge variant="accent">Administrator</x-ui.badge>
                        @endif
                        @if($officeActive)
                            <x-ui.badge variant="success">Pracownik biurowy</x-ui.badge>
                        @elseif($office)
                            <x-ui.badge variant="secondary">Profil biurowy nieaktywny</x-ui.badge>
                        @endif
                        @if($user->employee)
                            <x-ui.badge variant="info">Powiązany pracownik stoczni</x-ui.badge>
                        @endif
                        @if($user->trashed())
                            <x-ui.badge variant="danger">Dezaktywowany</x-ui.badge>
                        @endif
                    </div>
                    <p class="profile-hero__lead mb-0">
                        Konto od {{ $user->created_at->format('d.m.Y') }}
                        @if($user->roles->isNotEmpty())
                            · {{ $user->roles->pluck('name')->join(', ') }}
                        @endif
                    </p>
                </div>
            </div>
        </x-ui.card>

        <div class="row g-3">
            {{-- Zatrudnienie --}}
            <div class="col-lg-6">
                <x-ui.card class="h-100" label="Zatrudnienie">
                    @if($office)
                        <div class="user-show__facts">
                            <div class="user-show__fact">
                                <span class="user-show__fact-label">Status w biurze</span>
                                <span class="user-show__fact-value">
                                    @if($officeActive)
                                        <x-ui.badge variant="success">Aktywny</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="secondary">Nieaktywny</x-ui.badge>
                                    @endif
                                </span>
                            </div>
                            <div class="user-show__fact">
                                <span class="user-show__fact-label">Umowa</span>
                                <span class="user-show__fact-value">{{ $office->contract_type?->label() ?? '—' }}</span>
                            </div>
                            <div class="user-show__fact">
                                <span class="user-show__fact-label">Rozliczenie</span>
                                <span class="user-show__fact-value">{{ $office->pay_mode?->label() ?? '—' }}</span>
                            </div>
                            <div class="user-show__fact">
                                <span class="user-show__fact-label">Część etatu</span>
                                <span class="user-show__fact-value font-mono">
                                    {{ $office->fte !== null ? number_format((float) $office->fte, 2, ',', ' ') : '—' }}
                                </span>
                            </div>
                            <div class="user-show__fact">
                                <span class="user-show__fact-label">Stawka</span>
                                <span class="user-show__fact-value font-mono">
                                    @if($office->hourly_rate !== null)
                                        {{ number_format((float) $office->hourly_rate, 2, ',', ' ') }}
                                        {{ $office->currency ?: 'PLN' }}
                                    @else
                                        —
                                    @endif
                                </span>
                            </div>
                            <div class="user-show__fact">
                                <span class="user-show__fact-label">Okres</span>
                                <span class="user-show__fact-value font-mono">
                                    {{ $office->started_on?->format('d.m.Y') ?? '—' }}
                                    →
                                    {{ $office->ended_on?->format('d.m.Y') ?? 'bezterminowo' }}
                                </span>
                            </div>
                        </div>
                        @if($office->notes)
                            <p class="user-show__notes mt-3 mb-0">{{ $office->notes }}</p>
                        @endif
                        <div class="mt-3">
                            <a href="{{ route('office-profiles.index') }}" class="btn btn-sm btn-outline-secondary">
                                Profile biurowe
                            </a>
                        </div>
                    @else
                        <div class="user-show__empty">
                            <i class="bi bi-building"></i>
                            <div>
                                <div class="fw-semibold">Brak profilu biurowego</div>
                                <p class="text-muted small mb-0">
                                    Ten użytkownik nie jest oznaczony jako pracownik biura.
                                    Możesz to włączyć na liście profili biurowych.
                                </p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('office-profiles.index') }}" class="btn btn-sm btn-outline-secondary">
                                Przejdź do profili biurowych
                            </a>
                        </div>
                    @endif

                    @if($user->employee)
                        <hr class="user-show__divider">
                        <div class="user-show__fact">
                            <span class="user-show__fact-label">Pracownik stoczni</span>
                            <span class="user-show__fact-value">
                                <a href="{{ route('employees.show', $user->employee) }}">
                                    {{ $user->employee->full_name ?? $user->employee->name ?? ('#'.$user->employee->id) }}
                                </a>
                            </span>
                        </div>
                    @endif
                </x-ui.card>
            </div>

            {{-- Sesje i urządzenia --}}
            <div class="col-lg-6">
                <x-ui.card class="h-100" label="Sesje i urządzenia">
                    <p class="text-muted small mb-3">
                        Aktywne logowania i przeglądarki z włączonym push.
                    </p>

                    <div class="profile-list-label">Sesje ({{ count($sessions) }})</div>
                    @if(count($sessions) === 0)
                        <p class="text-muted small mb-3">Brak aktywnych sesji w bazie.</p>
                    @else
                        <div class="profile-list mb-3">
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
                                        <div class="profile-list__meta">
                                            {{ $session['ip'] }} · {{ $session['seen'] }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="profile-list-label">Push ({{ count($pushDevices) }})</div>
                    @if(count($pushDevices) === 0)
                        <p class="text-muted small mb-0">Żadna przeglądarka nie dostaje push.</p>
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
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-ui.card>
            </div>
        </div>

        {{-- Uprawnienia: role + projekty + katalog --}}
        <x-ui.card label="Uprawnienia i dostęp">
            <div
                class="user-access"
                x-data="{ openModules: true }"
            >
                @if($user->isAdmin())
                    <div class="user-access__banner">
                        <i class="bi bi-shield-check"></i>
                        <div>
                            <strong>Pełny dostęp administratora</strong>
                            <p class="mb-0 small text-muted">
                                Konto omija sprawdzanie ról — ma dostęp do całego systemu
                                (katalog poniżej to mapa możliwości, nie lista ręcznych nadań).
                            </p>
                        </div>
                    </div>
                @endif

                <div class="user-access__block">
                    <div class="user-access__block-head">
                        <h3 class="user-access__title">Role</h3>
                        <span class="user-access__count font-mono">{{ $user->roles->count() }}</span>
                    </div>
                    @if($user->roles->isEmpty())
                        <p class="text-muted small mb-0">Brak przypisanych ról.</p>
                    @else
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($user->roles as $role)
                                <x-ui.badge variant="primary">{{ $role->name }}</x-ui.badge>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="user-access__block">
                    <div class="user-access__block-head">
                        <h3 class="user-access__title">Kierownictwo projektów</h3>
                        <span class="user-access__count font-mono">{{ $user->managedProjects->count() }}</span>
                    </div>
                    @if($user->managedProjects->isEmpty())
                        <p class="text-muted small mb-0">Nie jest kierownikiem żadnego projektu.</p>
                    @else
                        <div class="user-access__projects">
                            @foreach($user->managedProjects as $project)
                                <div class="user-access__project">
                                    <div class="user-access__project-main">
                                        <span class="fw-semibold">{{ $project->name }}</span>
                                        @if($project->client_name)
                                            <span class="text-muted small">{{ $project->client_name }}</span>
                                        @endif
                                    </div>
                                    <x-ui.badge variant="info">Kierownik</x-ui.badge>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="user-access__block user-access__block--last">
                    <div class="user-access__block-head">
                        <div>
                            <h3 class="user-access__title mb-0">Katalog uprawnień</h3>
                            <p class="text-muted small mb-0 mt-1">
                                {{ $permissionTotal }} uprawnień w {{ $permissionGroups->count() }} obszarach
                                @unless($user->isAdmin())
                                    @if($directPermissionIds->isNotEmpty())
                                        · część nadana bezpośrednio
                                    @else
                                        · przez role
                                    @endif
                                @endunless
                            </p>
                        </div>
                        @if($permissionGroups->isNotEmpty())
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary"
                                @click="openModules = !openModules"
                                x-text="openModules ? 'Zwiń' : 'Rozwiń'"
                            ></button>
                        @endif
                    </div>

                    @if($permissionGroups->isEmpty())
                        <p class="text-muted small mb-0">Brak uprawnień.</p>
                    @else
                        <div class="user-access__modules" x-show="openModules" x-cloak>
                            @foreach($permissionGroups as $group)
                                <div class="user-access__module">
                                    <div class="user-access__module-head">
                                        <span class="user-access__module-name">{{ $group['label'] }}</span>
                                        <span class="user-access__module-key font-mono">{{ $group['key'] }}</span>
                                    </div>
                                    <div class="user-access__actions">
                                        @foreach($group['actions'] as $action)
                                            <span @class([
                                                'user-access__action',
                                                'user-access__action--view' => $action === 'view',
                                                'user-access__action--write' => in_array($action, ['create', 'update', 'store', 'edit'], true),
                                                'user-access__action--danger' => in_array($action, ['delete', 'destroy'], true),
                                            ])>{{ $action }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </x-ui.card>

        <div>
            <livewire:pulse.user-route-usage :user-id="$user->id" :expand="true" :key="'user-route-usage-'.$user->id" />
        </div>
    </div>

    <style>
        .user-show__hero { align-items: center; }
        .user-show__chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            margin: 0.55rem 0 0.35rem;
        }

        .user-show__facts {
            display: grid;
            gap: 0.65rem;
        }
        .user-show__fact {
            display: grid;
            grid-template-columns: 8.5rem 1fr;
            gap: 0.75rem;
            align-items: baseline;
        }
        .user-show__fact-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--text-muted);
        }
        .user-show__fact-value {
            font-weight: 560;
            color: var(--text-main);
            min-width: 0;
            word-break: break-word;
        }
        .user-show__notes {
            font-size: 0.85rem;
            color: var(--text-muted);
            padding: 0.65rem 0.75rem;
            border-radius: 0.5rem;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--glass-border, rgba(255,255,255,0.08));
        }
        .user-show__empty {
            display: flex;
            gap: 0.85rem;
            align-items: flex-start;
        }
        .user-show__empty > i {
            font-size: 1.35rem;
            color: var(--text-muted);
            opacity: 0.8;
            margin-top: 0.1rem;
        }
        .user-show__divider {
            border-color: var(--glass-border, rgba(255,255,255,0.08));
            opacity: 1;
            margin: 1.1rem 0;
        }

        .user-access__banner {
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
            padding: 0.85rem 1rem;
            border-radius: 0.65rem;
            margin-bottom: 1.1rem;
            background: linear-gradient(135deg, rgba(59,130,246,0.12), rgba(168,85,247,0.12));
            border: 1px solid rgba(129, 140, 248, 0.28);
        }
        .user-access__banner > i {
            font-size: 1.25rem;
            color: #a5b4fc;
            margin-top: 0.1rem;
        }
        .user-access__block {
            padding-bottom: 1.15rem;
            margin-bottom: 1.15rem;
            border-bottom: 1px solid var(--glass-border, rgba(255,255,255,0.08));
        }
        .user-access__block--last {
            padding-bottom: 0;
            margin-bottom: 0;
            border-bottom: 0;
        }
        .user-access__block-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }
        .user-access__title {
            font-size: 0.95rem;
            font-weight: 650;
            margin: 0;
            color: var(--text-main);
        }
        .user-access__count {
            font-size: 0.75rem;
            color: var(--text-muted);
            padding: 0.15rem 0.45rem;
            border-radius: 999px;
            border: 1px solid var(--glass-border);
            background: rgba(255,255,255,0.03);
        }

        .user-access__projects {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
        }
        .user-access__project {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.55rem 0.7rem;
            border-radius: 0.55rem;
            border: 1px solid var(--glass-border, rgba(255,255,255,0.08));
            background: rgba(255,255,255,0.02);
        }
        .user-access__project-main {
            display: flex;
            flex-direction: column;
            gap: 0.1rem;
            min-width: 0;
        }

        .user-access__modules {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(14.5rem, 1fr));
            gap: 0.55rem;
        }
        .user-access__module {
            padding: 0.65rem 0.7rem;
            border-radius: 0.55rem;
            border: 1px solid var(--glass-border, rgba(255,255,255,0.08));
            background: rgba(13, 18, 30, 0.35);
            min-width: 0;
        }
        .user-access__module-head {
            display: flex;
            flex-direction: column;
            gap: 0.1rem;
            margin-bottom: 0.45rem;
        }
        .user-access__module-name {
            font-size: 0.82rem;
            font-weight: 650;
            line-height: 1.2;
        }
        .user-access__module-key {
            font-size: 0.62rem;
            color: var(--text-muted);
            opacity: 0.85;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .user-access__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.28rem;
        }
        .user-access__action {
            font-size: 0.62rem;
            font-weight: 650;
            font-family: 'JetBrains Mono', ui-monospace, monospace;
            padding: 0.12rem 0.38rem;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.12);
            color: var(--text-muted);
            background: rgba(255,255,255,0.03);
            text-transform: lowercase;
        }
        .user-access__action--view {
            color: #7dd3fc;
            border-color: rgba(56, 189, 248, 0.35);
            background: rgba(56, 189, 248, 0.1);
        }
        .user-access__action--write {
            color: #86efac;
            border-color: rgba(34, 197, 94, 0.35);
            background: rgba(34, 197, 94, 0.1);
        }
        .user-access__action--danger {
            color: #fca5a5;
            border-color: rgba(248, 113, 113, 0.35);
            background: rgba(248, 113, 113, 0.1);
        }

        @media (max-width: 575.98px) {
            .user-show__fact {
                grid-template-columns: 1fr;
                gap: 0.15rem;
            }
        }
    </style>
</x-app-layout>
