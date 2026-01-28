<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Kary i Nagrody">
            <x-slot name="right">
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('adjustments.create') }}"
                    routeName="adjustments.create"
                    action="create"
                >
                    Dodaj Karę/Nagrodę
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
        @if($adjustments->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Pracownik</th>
                            <th>Typ</th>
                            <th>Kwota</th>
                            <th>Data</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($adjustments as $adjustment)
                            <tr>
                                <td>
                                    <x-employee-cell :employee="$adjustment->employee"  />
                                </td>
                                <td>
                                    <x-ui.badge variant="{{ $adjustment->type === 'bonus' ? 'success' : 'danger' }}">
                                        {{ $adjustment->type === 'bonus' ? 'Nagroda' : 'Kara' }}
                                    </x-ui.badge>
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
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            @if($adjustments->hasPages())
                <div class="mt-3">
                    <x-ui.pagination :paginator="$adjustments" />
                </div>
            @endif
        @else
            <x-ui.empty-state 
                icon="inbox" 
                message="Brak kar/nagród w systemie."
            >
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('adjustments.create') }}"
                    routeName="adjustments.create"
                    action="create"
                >
                    Dodaj pierwszą karę/nagrodę
                </x-ui.button>
            </x-ui.empty-state>
        @endif
    </x-ui.card>
</x-app-layout>
