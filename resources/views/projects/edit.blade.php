<x-app-layout>
    <div class="row justify-content-center">
        <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom">
                        <h2 class="h4 fw-semibold text-dark mb-0">Edytuj Projekt</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('projects.update', $project) }}">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nazwa Projektu</label>
                                <input type="text" name="name" value="{{ old('name', $project->name) }}" required
                                    class="form-control @error('name') is-invalid @enderror">
                                @error('name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Klient</label>
                                <input type="text" name="client_name" value="{{ old('client_name', $project->client_name) }}"
                                    class="form-control">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Opis</label>
                                <textarea name="description" rows="4"
                                    class="form-control">{{ old('description', $project->description) }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="status" required
                                    class="form-select @error('status') is-invalid @enderror">
                                    <option value="active" {{ $project->status == 'active' ? 'selected' : '' }}>Aktywny</option>
                                    <option value="on_hold" {{ $project->status == 'on_hold' ? 'selected' : '' }}>Wstrzymany</option>
                                    <option value="completed" {{ $project->status == 'completed' ? 'selected' : '' }}>Zakończony</option>
                                    <option value="cancelled" {{ $project->status == 'cancelled' ? 'selected' : '' }}>Anulowany</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Budżet (PLN)</label>
                                <input type="number" step="0.01" name="budget" value="{{ old('budget', $project->budget) }}"
                                    class="form-control">
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Aktualizuj
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
