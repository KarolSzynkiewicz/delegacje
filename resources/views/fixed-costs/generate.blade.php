<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Generuj Koszty Stałe">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('fixed-costs.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Generuj Koszty Stałe">
                <form action="{{ route('fixed-costs.generate.store') }}" method="POST">
                    @csrf

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <x-ui.input 
                                type="date" 
                                name="period_start" 
                                label="Data od"
                                value="{{ old('period_start') }}"
                                required="true"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input 
                                type="date" 
                                name="period_end" 
                                label="Data do"
                                value="{{ old('period_end') }}"
                                required="true"
                            />
                        </div>
                    </div>

                    <div class="mb-4">
                        <x-ui.input 
                            type="textarea" 
                            name="notes" 
                            label="Notatki (opcjonalnie)"
                            value="{{ old('notes') }}"
                            rows="3"
                        />
                    </div>

                    <div class="d-flex justify-content-end gap-2">
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
                            Generuj Koszty Stałe
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>

            <x-ui.card label="Informacje" class="mt-4">
                <p class="text-muted mb-0">
                    System wygeneruje koszty stałe dla wszystkich aktywnych szablonów w wybranym okresie.
                    Koszty, które już istnieją dla danego okresu, zostaną pominięte.
                </p>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
