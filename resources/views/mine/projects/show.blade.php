<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Projekt zespołu: {{ $project->name }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('mine.projects.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="container-xxl pj-shell">
        @include('livewire.partials.project-show-styles')
        @include('projects.partials.project-hero', ['project' => $project->loadMissing(['client', 'clientContact', 'location'])])
        <livewire:project-tabs :project="$project" :isMineView="true" />
    </div>
</x-app-layout>
