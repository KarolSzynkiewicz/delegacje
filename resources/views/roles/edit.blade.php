<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-semibold fs-4 text-dark mb-0">Edytuj Rolę</h2>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form method="POST" action="{{ route('roles.update', $role) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <x-input-label for="name" value="Nazwa" />
                            <span class="text-danger">*</span>
                            <x-text-input id="name" name="name" type="text" class="mt-1" :value="old('name', $role->name)" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div class="mb-3">
                            <x-input-label for="description" value="Opis" />
                            <textarea id="description" name="description" rows="4" class="form-control mt-1">{{ old('description', $role->description) }}</textarea>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <x-primary-button>
                                <i class="bi bi-check-circle me-1"></i> Zapisz
                            </x-primary-button>
                            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-1"></i> Anuluj
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
