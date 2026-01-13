<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Zaliczki</h2>
            <x-ui.button variant="primary" href="{{ route('advances.create') }}">
                <i class="bi bi-plus-circle"></i> Dodaj Zaliczkę
            </x-ui.button>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <x-ui.card>
                @if (session('success'))
                    <x-ui.alert variant="success" dismissible>
                        {{ session('success') }}
                    </x-ui.alert>
                @endif

                @if($advances->count() > 0)
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
                                @foreach ($advances as $advance)
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
                                                <x-ui.badge variant="warning">{{ number_format($advance->interest_rate, 2, ',', ' ') }}%</x-ui.badge>
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
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    @if($advances->hasPages())
                        <div class="mt-3 pt-3 border-top">
                            {{ $advances->links() }}
                        </div>
                    @endif
                @else
                    <x-ui.empty-state 
                        icon="inbox"
                        message="Brak zaliczek w systemie."
                    >
                        <x-ui.button variant="primary" href="{{ route('advances.create') }}">
                            <i class="bi bi-plus-circle"></i> Dodaj pierwszą zaliczkę
                        </x-ui.button>
                    </x-ui.empty-state>
                @endif
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
