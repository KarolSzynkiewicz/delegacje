<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dodaj Szablon Kosztu Ogólnofirmowego">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('fixed-costs.tab.templates') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Dodaj Nowy Szablon Kosztu Ogólnofirmowego">
                <form method="POST" action="{{ route('fixed-costs.store') }}">
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

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="category" 
                            label="Kategoria kosztu (cost center)"
                        >
                            <option value="">— Brak / nie wybrano —</option>
                            @foreach(\App\Models\FixedCostTemplate::categoryOptions() as $key => $label)
                                <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </x-ui.input>
                        <small class="text-muted">Używane do grupowania na wykresach struktury kosztów stałych.</small>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <x-ui.input 
                                type="select" 
                                name="interval_type" 
                                label="Typ interwału"
                                required="true"
                            >
                                <option value="monthly" {{ old('interval_type') == 'monthly' ? 'selected' : '' }}>Miesięczny</option>
                                <option value="weekly" {{ old('interval_type') == 'weekly' ? 'selected' : '' }}>Tygodniowy</option>
                                <option value="yearly" {{ old('interval_type') == 'yearly' ? 'selected' : '' }}>Roczny</option>
                            </x-ui.input>
                        </div>
                        <div class="col-md-6">
                            <x-ui.input 
                                type="number" 
                                name="interval_day" 
                                label="Dzień interwału"
                                value="{{ old('interval_day', 1) }}"
                                min="1"
                                max="31"
                                required="true"
                            />
                            <small class="text-muted">
                                Dla miesięcznego: dzień miesiąca (1-31)<br>
                                Dla tygodniowego: dzień tygodnia (1-7)<br>
                                Dla rocznego: dzień roku (1-365)
                            </small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <x-ui.input 
                                type="date" 
                                name="start_date" 
                                label="Data rozpoczęcia obowiązywania (opcjonalne)"
                                value="{{ old('start_date') }}"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input 
                                type="date" 
                                name="end_date" 
                                label="Data zakończenia obowiązywania (opcjonalne)"
                                value="{{ old('end_date') }}"
                            />
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="is_active" 
                                   id="is_active" 
                                   value="1"
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Aktywny
                            </label>
                        </div>
                        <small class="text-muted">Tylko aktywne szablony będą generowane podczas tworzenia kosztów.</small>
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
                            href="{{ route('fixed-costs.tab.templates') }}"
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
