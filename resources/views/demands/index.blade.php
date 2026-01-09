<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">
                Zapotrzebowanie projektu: {{ $project->name }}
            </h2>
            <a href="{{ route('projects.demands.create', $project) }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Dodaj Zapotrzebowanie
            </a>
        </div>
    </x-slot>

    @if (session('success'))
        <x-alert type="success" dismissible icon="check-circle">
            {{ session('success') }}
        </x-alert>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start">Rola</th>
                            <th class="text-start">Liczba osób</th>
                            <th class="text-start">Od - Do</th>
                            <th class="text-start">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($demands as $demand)
                            <tr>
                                <td>
                                    <span class="badge bg-secondary">{{ $demand->role->name }}</span>
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $demand->required_count }}</span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        {{ $demand->date_from->format('Y-m-d') }}
                                        @if($demand->date_to)
                                            - {{ $demand->date_to->format('Y-m-d') }}
                                        @else
                                            - ...
                                        @endif
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('demands.show', $demand) }}" class="btn btn-outline-primary" title="Zobacz">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('demands.edit', $demand) }}" class="btn btn-outline-secondary" title="Edytuj">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('demands.destroy', $demand) }}" method="POST" class="d-inline" onsubmit="return confirm('Czy na pewno chcesz usunąć to zapotrzebowanie?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Usuń">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    Brak zapotrzebowań dla tego projektu
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($demands->hasPages())
                <div class="mt-3">
                    {{ $demands->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
