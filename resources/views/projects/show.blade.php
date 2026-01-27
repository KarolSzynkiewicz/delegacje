<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Projekt: {{ $project->name }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('projects.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('projects.edit', $project) }}"
                    routeName="projects.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="container-xxl">
        <div class="row">
            <div class="col-md-12">
                <livewire:project-tabs :project="$project" />
            </div>
        </div>
    </div>
</x-app-layout>
