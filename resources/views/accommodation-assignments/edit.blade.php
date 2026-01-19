<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj Przypisanie Mieszkania">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('accommodation-assignments.show', $accommodationAssignment) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj Przypisanie Mieszkania">
                <x-ui.errors />

                @if (session('success'))
                    <x-alert type="success" dismissible icon="check-circle">
                        {{ session('success') }}
                    </x-alert>
                @endif

                <form method="POST" action="{{ route('accommodation-assignments.update', $accommodationAssignment) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="employee_id" 
                            label="Pracownik"
                            required="true"
                        >
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ old('employee_id', $accommodationAssignment->employee_id) == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->full_name }}
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="accommodation_id" 
                            label="Mieszkanie"
                            required="true"
                        >
                            @foreach($accommodations as $acc)
                                <option value="{{ $acc->id }}" {{ old('accommodation_id', $accommodationAssignment->accommodation_id) == $acc->id ? 'selected' : '' }}>
                                    {{ $acc->name }} ({{ $acc->capacity }} miejsc) - {{ $acc->city }}
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="date" 
                            name="start_date" 
                            label="Data Rozpoczęcia"
                            value="{{ old('start_date', $accommodationAssignment->start_date->format('Y-m-d')) }}"
                            required="true"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="date" 
                            name="end_date" 
                            label="Data Zakończenia (opcjonalnie)"
                            value="{{ old('end_date', $accommodationAssignment->end_date ? $accommodationAssignment->end_date->format('Y-m-d') : '') }}"
                        />
                    </div>

                    <div class="mb-4">
                        <x-ui.input 
                            type="textarea" 
                            name="notes" 
                            label="Uwagi"
                            value="{{ old('notes', $accommodationAssignment->notes) }}"
                            rows="3"
                        />
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Aktualizuj
                        </x-ui.button>
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('accommodation-assignments.index') }}"
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
