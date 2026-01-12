<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-semibold fs-4 mb-0">
            Edytuj Dokument: {{ $employeeDocument->document->name ?? '-' }}
        </h2>
        <p class="text-muted small mb-0">Pracownik: {{ $employee->full_name }}</p>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <x-ui.card>
                        <x-ui.errors />

                        <form action="{{ route('employees.employee-documents.update', [$employee, $employeeDocument]) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <x-ui.input 
                                    type="select" 
                                    name="document_id" 
                                    label="Typ dokumentu"
                                    required
                                >
                                    <option value="">-- Wybierz dokument --</option>
                                    @foreach($documents as $document)
                                        <option value="{{ $document->id }}" {{ old('document_id', $employeeDocument->document_id) == $document->id ? 'selected' : '' }}>
                                            {{ $document->name }}
                                        </option>
                                    @endforeach
                                </x-ui.input>
                                <small class="text-muted d-block mt-1">
                                    <a href="{{ route('documents.create') }}" target="_blank" class="text-primary">Dodaj nowy typ dokumentu</a>
                                </small>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <x-ui.input 
                                        type="date" 
                                        name="valid_from" 
                                        label="Dokument ważny od"
                                        value="{{ old('valid_from', $employeeDocument->valid_from ? $employeeDocument->valid_from->format('Y-m-d') : '') }}"
                                        required
                                    />
                                </div>
                                <div class="col-md-6">
                                    <x-ui.input 
                                        type="date" 
                                        name="valid_to" 
                                        label="Dokument ważny do"
                                        value="{{ old('valid_to', $employeeDocument->valid_to?->format('Y-m-d')) }}"
                                    />
                                    <small class="text-muted d-block mt-1">Wymagane dla dokumentów okresowych</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <x-ui.input 
                                    type="checkbox" 
                                    name="is_okresowy" 
                                    label="Dokument okresowy (z datą ważności do)"
                                    value="1"
                                    :checked="old('is_okresowy', $employeeDocument->kind === 'okresowy')"
                                />
                                <small class="text-muted d-block mt-1">Odznacz, jeśli dokument jest bezokresowy</small>
                            </div>

                            <div class="mb-3">
                                <x-ui.input 
                                    type="textarea" 
                                    name="notes" 
                                    label="Notatki"
                                    rows="3"
                                    value="{{ old('notes', $employeeDocument->notes) }}"
                                />
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Załącz plik</label>
                                @if($employeeDocument->file_path)
                                    <div class="mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <x-ui.button variant="ghost" href="{{ $employeeDocument->file_url }}" target="_blank" class="btn-sm">
                                                <i class="bi bi-file-earmark"></i>
                                                Pobierz obecny plik
                                            </x-ui.button>
                                        </div>
                                        <small class="text-muted d-block mt-1">Obecny plik: {{ basename($employeeDocument->file_path) }}</small>
                                    </div>
                                    <x-ui.input 
                                        type="checkbox" 
                                        id="remove_file" 
                                        name="remove_file" 
                                        value="1"
                                        label="Usuń obecny plik"
                                        class="mb-2"
                                    />
                                @endif
                                <x-ui.input 
                                    type="file" 
                                    name="file" 
                                    accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.txt"
                                />
                                <small class="text-muted d-block mt-1">Dozwolone formaty: PDF, DOC, DOCX, XLS, XLSX, ODT, TXT (max 10MB). Zostaw puste, aby zachować obecny plik.</small>
                            </div>

                            <div class="d-flex gap-2">
                                <x-ui.button variant="primary" type="submit">Zaktualizuj Dokument</x-ui.button>
                                <x-ui.button variant="ghost" href="{{ route('employees.show', $employee) }}">Anuluj</x-ui.button>
                            </div>
                        </form>
                    </x-ui.card>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Automatycznie wyczyść pole valid_to gdy odznaczono checkbox
        document.getElementById('is_okresowy').addEventListener('change', function() {
            const validToField = document.getElementById('valid_to');
            if (!this.checked) {
                validToField.value = '';
                validToField.disabled = true;
            } else {
                validToField.disabled = false;
            }
        });

        // Wywołaj przy załadowaniu strony
        document.getElementById('is_okresowy').dispatchEvent(new Event('change'));
    </script>
</x-app-layout>
