<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Koszt Zmienny">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('project-variable-costs.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('project-variable-costs.edit', $projectVariableCost) }}"
                    routeName="project-variable-costs.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card>
                <x-ui.detail-list>
                    <x-ui.detail-item label="Projekt:">
                        <a href="{{ route('projects.show', $projectVariableCost->project) }}" class="text-decoration-none">
                            {{ $projectVariableCost->project->name }}
                        </a>
                    </x-ui.detail-item>
                    <x-ui.detail-item label="Nazwa:">{{ $projectVariableCost->name }}</x-ui.detail-item>
                    <x-ui.detail-item label="Kwota:">
                        <strong>{{ number_format($projectVariableCost->amount, 2) }} {{ $projectVariableCost->currency }}</strong>
                    </x-ui.detail-item>
                    @if($projectVariableCost->notes)
                        <x-ui.detail-item label="Notatki:" :full-width="true">{{ $projectVariableCost->notes }}</x-ui.detail-item>
                    @endif
                </x-ui.detail-list>
    </x-ui.card>
</x-app-layout>
