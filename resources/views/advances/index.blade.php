<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Zaliczki">
            <x-slot name="right">
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('advances.create') }}"
                    routeName="advances.create"
                    action="create"
                >
                    Dodaj Zaliczkę
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if (session('success'))
        <x-alert type="success" dismissible icon="check-circle">
            {{ session('success') }}
        </x-alert>
    @endif

    <x-ui.card>
        @if($advances->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Pracownik</th>
                            <th>Kwota</th>
                            <th>Oprocentowanie</th>
                            <th>Do odliczenia</th>
                            <th>Data</th>
                            <th>Akcje</th>
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
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('advances.create') }}"
                    routeName="advances.create"
                    action="create"
                >
                    Dodaj pierwszą zaliczkę
                </x-ui.button>
            </x-ui.empty-state>
        @endif
    </x-ui.card>
</x-app-layout>
