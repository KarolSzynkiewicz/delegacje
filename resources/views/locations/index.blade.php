<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Lokalizacje</h2>
            <a href="{{ route('locations.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Dodaj Lokalizację
            </a>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    @if($locations->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start">Nazwa</th>
                                        <th class="text-start">Adres</th>
                                        <th class="text-start">Miasto</th>
                                        <th class="text-start">Baza</th>
                                        <th class="text-start">Kontakt</th>
                                        <th class="text-start">Akcje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($locations as $location)
                                        <tr>
                                            <td class="fw-medium">{{ $location->name }}</td>
                                            <td>{{ $location->address }}</td>
                                            <td>{{ $location->city ?? '-' }}</td>
                                            <td>
                                                @if($location->is_base)
                                                    <span class="badge bg-success">Baza</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($location->contact_person)
                                                    <div>{{ $location->contact_person }}</div>
                                                    @if($location->phone)
                                                        <small class="text-muted d-block">{{ $location->phone }}</small>
                                                    @endif
                                                    @if($location->email)
                                                        <small class="text-muted d-block">{{ $location->email }}</small>
                                                    @endif
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                <x-action-buttons
                                                    viewRoute="{{ route('locations.show', $location) }}"
                                                    editRoute="{{ route('locations.edit', $location) }}"
                                                    deleteRoute="{{ route('locations.destroy', $location) }}"
                                                    deleteMessage="Czy na pewno chcesz usunąć tę lokalizację?"
                                                />
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                            <p class="text-muted mb-3">Brak lokalizacji w systemie.</p>
                            <a href="{{ route('locations.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Dodaj pierwszą lokalizację
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
