<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj Koszt Zmienny">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('project-variable-costs.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj Koszt Zmienny">
                <form method="POST" action="{{ route('project-variable-costs.update', $projectVariableCost) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="project_id" 
                            label="Projekt"
                            required="true"
                        >
                            <option value="">Wybierz projekt</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ old('project_id', $projectVariableCost->project_id) == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="name" 
                            label="Nazwa kosztu"
                            value="{{ old('name', $projectVariableCost->name) }}"
                            required="true"
                        />
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <x-ui.input 
                                type="number" 
                                name="amount" 
                                label="Kwota"
                                value="{{ old('amount', $projectVariableCost->amount) }}"
                                step="0.01"
                                min="0"
                                required="true"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input 
                                type="select" 
                                name="currency" 
                                label="Waluta"
                                required="true"
                            >
                                <option value="PLN" {{ old('currency', $projectVariableCost->currency) == 'PLN' ? 'selected' : '' }}>PLN</option>
                                <option value="EUR" {{ old('currency', $projectVariableCost->currency) == 'EUR' ? 'selected' : '' }}>EUR</option>
                                <option value="USD" {{ old('currency', $projectVariableCost->currency) == 'USD' ? 'selected' : '' }}>USD</option>
                                <option value="GBP" {{ old('currency', $projectVariableCost->currency) == 'GBP' ? 'selected' : '' }}>GBP</option>
                            </x-ui.input>
                        </div>
                    </div>

                    <div class="mb-4">
                        <x-ui.input 
                            type="textarea" 
                            name="notes" 
                            label="Notatki"
                            value="{{ old('notes', $projectVariableCost->notes) }}"
                            rows="3"
                        />
                    </div>

                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('project-variable-costs.index') }}"
                            action="cancel"
                        >
                            Anuluj
                        </x-ui.button>
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Aktualizuj
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
