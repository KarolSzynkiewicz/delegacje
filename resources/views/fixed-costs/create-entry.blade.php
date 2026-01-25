<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dodaj Koszt Niestandardowy">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('fixed-costs.tab.entries') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Dodaj Nowy Koszt Niestandardowy">
                <form method="POST" action="{{ route('fixed-cost-entries.store') }}">
                    @csrf

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="name" 
                            label="Nazwa kosztu"
                            value="{{ old('name') }}"
                            required="true"
                        />
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <x-ui.input 
                                type="number" 
                                name="amount" 
                                label="Kwota"
                                value="{{ old('amount') }}"
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
                                <option value="PLN" {{ old('currency', 'PLN') == 'PLN' ? 'selected' : '' }}>PLN</option>
                                <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR</option>
                                <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD</option>
                                <option value="GBP" {{ old('currency') == 'GBP' ? 'selected' : '' }}>GBP</option>
                            </x-ui.input>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <x-ui.input 
                                type="date" 
                                name="period_start" 
                                label="Okres od"
                                value="{{ old('period_start') }}"
                                required="true"
                            />
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <x-ui.input 
                                type="date" 
                                name="period_end" 
                                label="Okres do"
                                value="{{ old('period_end') }}"
                                required="true"
                            />
                        </div>
                        <div class="col-md-4">
                            <x-ui.input 
                                type="date" 
                                name="accounting_date" 
                                label="Data księgowania"
                                value="{{ old('accounting_date', date('Y-m-d')) }}"
                                required="true"
                            />
                        </div>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="template_id" 
                            label="Szablon (opcjonalnie)"
                        >
                            <option value="">Brak szablonu</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}" {{ old('template_id') == $template->id ? 'selected' : '' }}>
                                    {{ $template->name }}
                                </option>
                            @endforeach
                        </x-ui.input>
                        <small class="text-muted">Możesz powiązać ten koszt z szablonem, jeśli został wygenerowany ręcznie z istniejącego szablonu.</small>
                    </div>

                    <div class="mb-4">
                        <x-ui.input 
                            type="textarea" 
                            name="notes" 
                            label="Notatki"
                            value="{{ old('notes') }}"
                            rows="3"
                        />
                    </div>

                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('fixed-costs.index') }}"
                            action="cancel"
                        >
                            Anuluj
                        </x-ui.button>
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Zapisz
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
