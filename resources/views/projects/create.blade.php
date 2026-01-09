<x-app-layout>
    <div class="row justify-content-center">
        <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom">
                        <h2 class="h4 fw-semibold text-dark mb-0">Dodaj Nowy Projekt</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('projects.store') }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Lokalizacja <span class="text-danger">*</span></label>
                                <select name="location_id" required
                                    class="form-select @error('location_id') is-invalid @enderror">
                                    <option value="">Wybierz lokalizację</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                            {{ $location->name }} ({{ $location->address }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('location_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nazwa Projektu <span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ old('name') }}" required
                                    class="form-control @error('name') is-invalid @enderror">
                                @error('name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Klient</label>
                                <input type="text" name="client_name" value="{{ old('client_name') }}"
                                    class="form-control">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Opis</label>
                                <textarea name="description" rows="4"
                                    class="form-control">{{ old('description') }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                                <select name="status" required
                                    class="form-select @error('status') is-invalid @enderror">
                                    <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Aktywny</option>
                                    <option value="on_hold" {{ old('status') == 'on_hold' ? 'selected' : '' }}>Wstrzymany</option>
                                    <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Zakończony</option>
                                    <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Anulowany</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Budżet (PLN)</label>
                                <input type="number" step="0.01" name="budget" value="{{ old('budget') }}"
                                    class="form-control">
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Zapisz
                                </button>
                                <a href="{{ route('projects.index') }}" class="btn btn-link text-decoration-none">Anuluj</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
