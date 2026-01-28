<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj Użytkownika: {{ $user->name }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('users.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj Użytkownika">
                <x-ui.errors />

                <form method="POST" action="{{ route('users.update', $user) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="name" 
                            label="Nazwa"
                            value="{{ old('name', $user->name) }}"
                            required="true"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="email" 
                            name="email" 
                            label="Email"
                            value="{{ old('email', $user->email) }}"
                            required="true"
                        />
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Role</label>
                        <div class="border rounded p-3 bg-light">
                            @php
                                $selectedRoles = old('roles', $user->roles->pluck('id')->toArray());
                            @endphp
                            <div class="d-flex flex-column gap-2">
                                @foreach($roles as $role)
                                    <div class="form-check">
                                        <input 
                                            class="form-check-input" 
                                            type="checkbox" 
                                            name="roles[]" 
                                            value="{{ $role->id }}"
                                            id="role_{{ $role->id }}"
                                            {{ in_array($role->id, $selectedRoles) ? 'checked' : '' }}
                                        >
                                        <label class="form-check-label" for="role_{{ $role->id }}">
                                            {{ $role->name }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @error('roles')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Zaktualizuj
                        </x-ui.button>
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('users.index') }}"
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
