<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-semibold fs-4 mb-0">
            Dodaj Dokument dla: {{ $employee->full_name }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <x-ui.card>
                        <x-ui.errors />

                        <form action="{{ route('employee-documents.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="employee_id" value="{{ $employee->id }}">

                            <div class="mb-3">
                                <x-ui.input 
                                    type="select" 
                                    name="document_id" 
                                    label="Typ dokumentu"
                                    required
                                >
                                    <option value="">-- Wybierz dokument --</option>
                                    @foreach($documents as $document)
                                        <option value="{{ $document->id }}" {{ old('document_id', $selectedDocumentId ?? null) == $document->id ? 'selected' : '' }}>
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
                                        value="{{ old('valid_from') }}"
                                        required
                                    />
                                </div>
                                <div class="col-md-6">
                                    <x-ui.input 
                                        type="date" 
                                        name="valid_to" 
                                        label="Dokument ważny do"
                                        value="{{ old('valid_to') }}"
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
                                    :checked="old('is_okresowy', true)"
                                />
                                <small class="text-muted d-block mt-1">Odznacz, jeśli dokument jest bezokresowy</small>
                            </div>

                            <div class="mb-3">
                                <x-ui.input 
                                    type="textarea" 
                                    name="notes" 
                                    label="Notatki"
                                    rows="3"
                                    value="{{ old('notes') }}"
                                />
                            </div>

                            <div class="mb-3">
                                <x-ui.input 
                                    type="file" 
                                    name="file" 
                                    label="Załącz plik"
                                    accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.txt"
                                />
                                <small class="text-muted d-block mt-1">Dozwolone formaty: PDF, DOC, DOCX, XLS, XLSX, ODT, TXT (max 10MB)</small>
                            </div>

                            <div class="d-flex gap-2">
                                <x-ui.button variant="primary" type="submit">Dodaj Dokument</x-ui.button>
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
