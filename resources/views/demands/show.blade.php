<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Zapotrzebowanie: {{ $demand->role->name }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('projects.demands.index', $demand->project) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('demands.edit', $demand) }}"
                    routeName="demands.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <x-ui.card label="Szczegóły Zapotrzebowania">
                <x-ui.detail-list>
                    <x-ui.detail-item label="Projekt">
                        <a href="{{ route('projects.show', $demand->project) }}" class="text-primary text-decoration-none">
                            {{ $demand->project->name }}
                        </a>
                    </x-ui.detail-item>
                    <x-ui.detail-item label="Rola">
                        <x-ui.badge variant="accent">{{ $demand->role->name }}</x-ui.badge>
                    </x-ui.detail-item>
                    <x-ui.detail-item label="Liczba osób">
                        <span class="fw-semibold">{{ $demand->required_count }}</span>
                    </x-ui.detail-item>
                    <x-ui.detail-item label="Data rozpoczęcia">
                        {{ $demand->start_date->format('d.m.Y') }}
                    </x-ui.detail-item>
                    <x-ui.detail-item label="Data zakończenia">
                        {{ $demand->end_date ? $demand->end_date->format('d.m.Y') : 'Bieżące' }}
                    </x-ui.detail-item>
                    @if($demand->notes)
                    <x-ui.detail-item label="Uwagi" fullWidth>
                        {{ $demand->notes }}
                    </x-ui.detail-item>
                    @endif
                </x-ui.detail-list>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
