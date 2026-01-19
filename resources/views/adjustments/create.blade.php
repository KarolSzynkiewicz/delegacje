<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dodaj Karę/Nagrodę">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('adjustments.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Dodaj Karę/Nagrodę">
                <form method="POST" action="{{ route('adjustments.store') }}">
                    @csrf

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="payroll_id" 
                            id="payroll_id"
                            label="Payroll"
                            required="true"
                        >
                            <option value="">Wybierz payroll</option>
                            @foreach($payrolls as $payroll)
                                <option value="{{ $payroll->id }}" {{ old('payroll_id') == $payroll->id ? 'selected' : '' }}>
                                    {{ $payroll->display_name }}
                                </option>
                            @endforeach
                        </x-ui.input>
                        <small class="form-text text-muted">Wybierz payroll, do którego przypisać karę/nagrodę</small>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <x-ui.input 
                                type="select" 
                                name="type" 
                                id="type"
                                label="Typ"
                                required="true"
                            >
                                <option value="penalty" {{ old('type', 'penalty') == 'penalty' ? 'selected' : '' }}>Kara</option>
                                <option value="bonus" {{ old('type') == 'bonus' ? 'selected' : '' }}>Nagroda</option>
                            </x-ui.input>
                        </div>
                        <div class="col-md-6">
                            <x-ui.input 
                                type="date" 
                                name="date" 
                                id="date"
                                label="Data"
                                value="{{ old('date') }}"
                                required="true"
                            />
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <x-ui.input 
                                type="number" 
                                name="amount" 
                                id="amount"
                                label="Kwota"
                                value="{{ old('amount') }}"
                                step="0.01"
                                required="true"
                            />
                            <small class="form-text text-muted">Dodatnia dla nagrody, ujemna dla kary</small>
                        </div>
                        <div class="col-md-6">
                            <x-ui.input 
                                type="select" 
                                name="currency" 
                                id="currency"
                                label="Waluta"
                                required="true"
                            >
                                <option value="PLN" {{ old('currency', 'PLN') == 'PLN' ? 'selected' : '' }}>PLN</option>
                                <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR</option>
                                <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD</option>
                            </x-ui.input>
                        </div>
                    </div>

                    <div class="mb-4">
                        <x-ui.input 
                            type="textarea" 
                            name="notes" 
                            id="notes"
                            label="Notatki"
                            value="{{ old('notes') }}"
                            rows="4"
                        />
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Zapisz
                        </x-ui.button>
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('adjustments.index') }}"
                            action="cancel"
                        >
                            Anuluj
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
