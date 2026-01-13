<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Zaliczki</h2>
            <x-ui.button variant="primary" href="{{ route('advances.create') }}">
                <i class="bi bi-plus-circle"></i> Dodaj Zaliczkę
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
                            <th class="text-start">Kwota</th>
                            <th class="text-start">Oprocentowanie</th>
                            <th class="text-start">Do odliczenia</th>
                            <th class="text-start">Data</th>
                            <th class="text-start">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($advances as $advance)
                            <tr>
                                <td>
                                    <a href="{{ route('employees.show', $advance->employee) }}" class="text-primary text-decoration-none">
                                        {{ $advance->employee->full_name }}
                                    </a>
                                </td>
                                <td>
                                    <strong>{{ number_format($advance->amount, 2, ',', ' ') }} {{ $advance->currency }}</strong>
                                </td>
                                <td>
                                    @if($advance->is_interest_bearing && $advance->interest_rate)
                                        <span class="badge bg-warning">{{ number_format($advance->interest_rate, 2, ',', ' ') }}%</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <strong class="text-danger">{{ number_format($advance->getTotalDeductionAmount(), 2, ',', ' ') }} {{ $advance->currency }}</strong>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $advance->date->format('Y-m-d') }}</small>
                                </td>
                                <td>
                                    <x-action-buttons
                                        viewRoute="{{ route('advances.show', $advance) }}"
                                        editRoute="{{ route('advances.edit', $advance) }}"
                                        deleteRoute="{{ route('advances.destroy', $advance) }}"
                                        deleteMessage="Czy na pewno chcesz usunąć tę zaliczkę?"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    Brak zaliczek
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($advances->hasPages())
                <div class="mt-3">
                    {{ $advances->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
