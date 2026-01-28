<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Zwróć/Zgłoś Sprzęt: {{ $equipmentIssue->equipment->name }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('equipment-issues.show', $equipmentIssue) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Informacje o wydaniu">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <h6 class="text-muted small mb-1">Pracownik</h6>
                        <p class="fw-semibold mb-0">{{ $equipmentIssue->employee->full_name }}</p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-muted small mb-1">Data wydania</h6>
                        <p class="fw-semibold mb-0">{{ $equipmentIssue->issue_date->format('Y-m-d') }}</p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-muted small mb-1">Ilość</h6>
                        <p class="fw-semibold mb-0">{{ $equipmentIssue->quantity_issued }} {{ $equipmentIssue->equipment->unit }}</p>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card label="Zwróć/Zgłoś sprzęt">
                <x-ui.errors />

                <form method="POST" action="{{ route('equipment-issues.return.store', $equipmentIssue) }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">
                            Status <span class="text-danger">*</span>
                        </label>
                        <select 
                            name="status" 
                            class="form-control @error('status') is-invalid @enderror"
                            required
                        >
                            <option value="returned" {{ old('status', 'returned') === 'returned' ? 'selected' : '' }}>Zwrócony</option>
                            <option value="damaged" {{ old('status') === 'damaged' ? 'selected' : '' }}>Uszkodzony</option>
                            <option value="lost" {{ old('status') === 'lost' ? 'selected' : '' }}>Zgubiony</option>
                        </select>
                        @error('status')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="date" 
                            name="return_date" 
                            label="Data zwrotu/zgłoszenia *"
                            value="{{ old('return_date', date('Y-m-d')) }}"
                            min="{{ $equipmentIssue->issue_date->format('Y-m-d') }}"
                            required="true"
                        />
                    </div>

                    <div class="mb-4">
                        <x-ui.input 
                            type="textarea" 
                            name="notes" 
                            label="Notatki"
                            value="{{ old('notes', $equipmentIssue->notes) }}"
                            rows="3"
                        />
                    </div>

                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('equipment-issues.show', $equipmentIssue) }}"
                            action="cancel"
                        >
                            Anuluj
                        </x-ui.button>
                        <x-ui.button 
                            variant="success" 
                            type="submit"
                            action="save"
                        >
                            Zatwierdź
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
