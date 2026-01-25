<x-app-layout>
    <x-slot name="header">
        
        
        <x-ui.page-header title="Wydania Sprzętu">
            <x-slot name="right">
                <x-ui.button 
                    variant="primary"
                    href="{{ route('equipment-issues.create') }}"
                    action="create">
                    Wydaj Sprzęt
                </x-ui.button>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card>
                @if($issues->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Sprzęt</th>
                                    <th>Pracownik</th>
                                    <th>Ilość</th>
                                    <th>Data wydania</th>
                                    <th>Status</th>
                                    <th>Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($issues as $issue)
                                    <tr>
                                        <td>{{ $issue->equipment->name }}</td>
                                        <td>
                                            <x-employee-cell :employee="$issue->employee"  />
                                        </td>
                                        <td>{{ $issue->quantity_issued }} {{ $issue->equipment->unit }}</td>
                                        <td>{{ $issue->issue_date->format('Y-m-d') }}</td>
                                        <td>
                                            @php
                                                $badgeVariant = match($issue->status) {
                                                    'issued' => 'info',
                                                    'returned' => 'success',
                                                    default => 'info'
                                                };
                                            @endphp
                                            <x-ui.badge variant="{{ $badgeVariant }}">{{ ucfirst($issue->status) }}</x-ui.badge>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <x-view-button href="{{ route('equipment-issues.show', $issue) }}" />
                                                @if($issue->status === 'issued')
                                                    <x-ui.button variant="success" href="{{ route('equipment-issues.return', $issue) }}" class="btn-sm" title="Zwróć">
                                                        <i class="bi bi-arrow-return-left"></i>
                                                    </x-ui.button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($issues->hasPages())
                        <div class="mt-3">
                            {{ $issues->links() }}
                        </div>
                    @endif
                @else
                    <x-ui.empty-state 
                        icon="inbox" 
                        message="Brak wydań w systemie."
                    >
                        <x-ui.button 
                            variant="primary" 
                            href="{{ route('equipment-issues.create') }}"
                            action="create"
                        >
                            Wydaj pierwszy sprzęt
                        </x-ui.button>
                    </x-ui.empty-state>
                @endif
    </x-ui.card>
</x-app-layout>
