<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-semibold fs-4 mb-0">Edytuj Pracownika: {{ $employee->full_name }}</h2>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj Pracownika">
                <x-ui.errors />

                <form action="{{ route('employees.update', $employee) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="first_name" 
                            label="Imię"
                            value="{{ old('first_name', $employee->first_name) }}"
                            required="true"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="last_name" 
                            label="Nazwisko"
                            value="{{ old('last_name', $employee->last_name) }}"
                            required="true"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="email" 
                            name="email" 
                            label="Email"
                            value="{{ old('email', $employee->email) }}"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="phone" 
                            label="Telefon"
                            value="{{ old('phone', $employee->phone) }}"
                        />
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <x-ui.input
                                type="text"
                                name="shoe_size"
                                label="Rozmiar buta"
                                value="{{ old('shoe_size', $employee->shoe_size) }}"
                                placeholder="np. 42"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input
                                type="text"
                                name="pants_size"
                                label="Rozmiar spodni"
                                value="{{ old('pants_size', $employee->pants_size) }}"
                                placeholder="np. 52"
                            />
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="has_komornik"
                                id="has_komornik"
                                value="1"
                                {{ old('has_komornik', $employee->has_komornik) ? 'checked' : '' }}
                            >
                            <label class="form-check-label" for="has_komornik">Komornik</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Role *</label>
                        <div class="border rounded p-3 @error('roles') border-danger @enderror">
                            @foreach ($roles as $role)
                                <div class="form-check @error('roles') is-invalid @enderror">
                                    <input 
                                        type="checkbox" 
                                        id="role_{{ $role->id }}" 
                                        name="roles[]" 
                                        value="{{ $role->id }}"
                                        {{ in_array($role->id, old('roles', $employee->roles->pluck('id')->toArray())) ? 'checked' : '' }}
                                        class="@error('roles') is-invalid @enderror"
                                    />
                                    <label for="role_{{ $role->id }}">
                                        {{ $role->name }}
                                        @if($role->description)
                                            <small class="text-muted d-block">({{ $role->description }})</small>
                                        @endif
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('roles') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                        <small class="form-text text-muted">Wybierz przynajmniej jedną rolę. Pracownik może mieć wiele ról jednocześnie.</small>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="textarea" 
                            name="notes" 
                            label="Notatki"
                            value="{{ old('notes', $employee->notes) }}"
                            rows="4"
                        />
                    </div>

                    <x-ui.image-preview 
                        :showCurrentImage="$employee->image_path ? true : false"
                        :currentImageUrl="$employee->image_path ? $employee->image_url : null"
                        :currentImage="$employee->full_name"
                    />

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button variant="primary" type="submit">
                            <i class="bi bi-save me-1"></i> Zaktualizuj Pracownika
                        </x-ui.button>
                        <x-ui.button variant="ghost" href="{{ route('employees.show', $employee) }}">Anuluj</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
