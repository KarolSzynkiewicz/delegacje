<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-semibold fs-4 mb-0">Dodaj Nowego Pracownika</h2>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Dodaj Nowego Pracownika">
                <x-ui.errors />

                <form action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="first_name" 
                            label="Imię"
                            value="{{ old('first_name') }}"
                            required="true"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="last_name" 
                            label="Nazwisko"
                            value="{{ old('last_name') }}"
                            required="true"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="email" 
                            name="email" 
                            label="Email"
                            value="{{ old('email') }}"
                            required="true"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="phone" 
                            label="Telefon"
                            value="{{ old('phone') }}"
                        />
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
                                        {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }}
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
                            value="{{ old('notes') }}"
                            rows="4"
                        />
                    </div>

                    <x-ui.image-preview />

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button variant="primary" type="submit">
                            <i class="bi bi-save me-1"></i> Dodaj Pracownika
                        </x-ui.button>
                        <x-ui.button variant="ghost" href="{{ route('employees.index') }}">Anuluj</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
