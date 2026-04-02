<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Wszystkie zapotrzebowania projektów">
            <x-slot name="right">
                @php
                    $firstProject = \App\Models\Project::orderBy('name')->first();
                @endphp
                @if($firstProject)
                    <x-ui.button
                        variant="primary"
                        href="{{ route('projects.demands.create', $firstProject) }}"
                        action="create"
                    >
                        Dodaj zapotrzebowanie
                    </x-ui.button>
                @endif
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if (session('success'))
        <x-alert type="success" dismissible icon="check-circle">
            {{ session('success') }}
        </x-alert>
    @endif

    <x-ui.card class="mb-4">
        <form method="GET" action="{{ route('project-demands.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1 text-muted" for="searchProject">Nazwa projektu</label>
                <input
                    id="searchProject"
                    type="text"
                    name="searchProject"
                    value="{{ request('searchProject') }}"
                    placeholder="Fragment nazwy…"
                    class="form-control"
                >
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1 text-muted" for="roleFilter">Rola</label>
                <select id="roleFilter" name="roleFilter" class="form-select">
                    <option value="">Wszystkie role</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" @selected((string) request('roleFilter') === (string) $role->id)>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex flex-wrap gap-2">
                <x-ui.button type="submit" variant="primary">
                    Filtruj
                </x-ui.button>
                @if(request()->filled('searchProject') || request()->filled('roleFilter'))
                    <x-ui.button variant="ghost" href="{{ route('project-demands.index') }}">
                        <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                    </x-ui.button>
                @endif
            </div>
        </form>
    </x-ui.card>

    <x-ui.card>
        @if($demands->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-start">ID</th>
                            <th class="text-start">Projekt</th>
                            <th class="text-start">Rola</th>
                            <th class="text-start">Liczba osób</th>
                            <th class="text-start">Od – Do</th>
                            <th class="text-start">Status</th>
                            <th class="text-end" style="width: 120px;">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($demands as $demand)
                            <tr>
                                <td class="text-muted small">
                                    {{ $demand->id }}
                                </td>
                                <td>
                                    <a href="{{ route('projects.show', $demand->project) }}" class="text-decoration-none fw-medium">
                                        <i class="bi bi-folder me-1 text-muted"></i>{{ $demand->project->name }}
                                    </a>
                                </td>
                                <td>
                                    <x-ui.badge variant="accent">{{ $demand->role->name }}</x-ui.badge>
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $demand->required_count }}</span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        {{ $demand->start_date->format('Y-m-d') }}
                                        @if($demand->end_date)
                                            – {{ $demand->end_date->format('Y-m-d') }}
                                        @else
                                            – …
                                        @endif
                                    </small>
                                </td>
                                <td>
                                    @if($demand->isActive())
                                        <x-ui.badge variant="success">Aktywne</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="secondary">Zakończone</x-ui.badge>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <x-action-buttons
                                        viewRoute="{{ route('demands.show', $demand) }}"
                                        editRoute="{{ route('demands.edit', $demand) }}"
                                        deleteRoute="{{ route('demands.destroy', $demand) }}"
                                        deleteMessage="Czy na pewno chcesz usunąć to zapotrzebowanie?"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($demands->hasPages())
                <div class="mt-3 pt-3 border-top">
                    <x-ui.pagination :paginator="$demands" />
                </div>
            @endif
        @else
            <x-ui.empty-state
                icon="inbox"
                message="{{ request()->filled('searchProject') || request()->filled('roleFilter') ? 'Brak zapotrzebowań dla wybranych filtrów.' : 'Brak zapotrzebowań' }}"
            />
        @endif
    </x-ui.card>
</x-app-layout>
