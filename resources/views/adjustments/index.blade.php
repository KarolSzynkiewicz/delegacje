<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Kary i Nagrody</h2>
            <x-ui.button variant="primary" href="{{ route('adjustments.create') }}">
                <i class="bi bi-plus-circle"></i> Dodaj Karę/Nagrodę
            </x-ui.button>
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
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th class="text-start">Pracownik</th>
                            <th class="text-start">Typ</th>
                            <th class="text-start">Kwota</th>
                            <th class="text-start">Data</th>
                            <th class="text-start">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($adjustments as $adjustment)
                            <tr>
                                <td>
                                    <a href="{{ route('employees.show', $adjustment->employee) }}" class="text-primary text-decoration-none">
                                        {{ $adjustment->employee->full_name }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge {{ $adjustment->type === 'bonus' ? 'bg-success' : 'bg-danger' }}">
                                        {{ $adjustment->type === 'bonus' ? 'Nagroda' : 'Kara' }}
                                    </span>
                                </td>
                                <td>
                                    <strong class="{{ $adjustment->amount >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($adjustment->amount, 2, ',', ' ') }} {{ $adjustment->currency }}
                                    </strong>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $adjustment->date->format('Y-m-d') }}</small>
                                </td>
                                <td>
                                    <x-action-buttons
                                        viewRoute="{{ route('adjustments.show', $adjustment) }}"
                                        editRoute="{{ route('adjustments.edit', $adjustment) }}"
                                        deleteRoute="{{ route('adjustments.destroy', $adjustment) }}"
                                        deleteMessage="Czy na pewno chcesz usunąć tę karę/nagrodę?"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    Brak kar/nagród
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($adjustments->hasPages())
                <div class="mt-3">
                    {{ $adjustments->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
