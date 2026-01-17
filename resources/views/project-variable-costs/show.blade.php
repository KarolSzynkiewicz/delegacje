<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Koszt Zmienny</h2>
            <div class="d-flex gap-2">
                <x-ui.button variant="ghost" href="{{ route('project-variable-costs.edit', $projectVariableCost) }}">
                    <i class="bi bi-pencil"></i> Edytuj
                </x-ui.button>
                <x-ui.button variant="ghost" href="{{ route('project-variable-costs.index') }}">
                    <i class="bi bi-arrow-left"></i> Powrót
                </x-ui.button>
            </div>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
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
        </div>
    </div>
</x-app-layout>
