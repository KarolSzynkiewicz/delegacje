<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Przypisz Dom do Pracownika">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('employees.accommodations.index', $employee) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Przypisz Dom do Pracownika">
                <x-ui.errors />

                <form method="POST" action="{{ route('employees.accommodations.store', $employee) }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pracownik</label>
                        <input type="text" value="{{ $employee->full_name }}" disabled
                            class="form-control bg-light">
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="accommodation_id" 
                            label="Mieszkanie"
                            required="true"
                        >
                            <option value="">Wybierz mieszkanie</option>
                            @foreach($accommodations as $accommodation)
                                <option value="{{ $accommodation->id }}" {{ old('accommodation_id') == $accommodation->id ? 'selected' : '' }}>
                                    {{ $accommodation->name }} ({{ $accommodation->capacity }} miejsc)
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="date" 
                            name="start_date" 
                            label="Data Rozpoczęcia"
                            value="{{ old('start_date', $dateFrom ?? '') }}"
                            required="true"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="date" 
                            name="end_date" 
                            label="Data Zakończenia (opcjonalnie)"
                            value="{{ old('end_date', $dateTo ?? '') }}"
                        />
                    </div>

                    <div class="mb-4">
                        <x-ui.input 
                            type="textarea" 
                            name="notes" 
                            label="Uwagi"
                            value="{{ old('notes') }}"
                            rows="3"
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
                            href="{{ route('employees.accommodations.index', $employee) }}"
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
