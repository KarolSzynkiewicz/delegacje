<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Wydania Sprzętu</h2>
            <x-ui.button variant="primary" href="{{ route('equipment-issues.create') }}">
                <i class="bi bi-plus-circle"></i> Wydaj Sprzęt
            </x-ui.button>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    @if($issues->count() > 0)
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th class="text-start">Sprzęt</th>
                                        <th class="text-start">Pracownik</th>
                                        <th class="text-start">Ilość</th>
                                        <th class="text-start">Data wydania</th>
                                        <th class="text-start">Status</th>
                                        <th class="text-start">Akcje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($issues as $issue)
                                        <tr>
                                            <td>{{ $issue->equipment->name }}</td>
                                            <td>{{ $issue->employee->full_name }}</td>
                                            <td>{{ $issue->quantity_issued }} {{ $issue->equipment->unit }}</td>
                                            <td>{{ $issue->issue_date->format('Y-m-d') }}</td>
                                            <td>
                                                @php
                                                    $badgeClass = match($issue->status) {
                                                        'issued' => 'bg-primary',
                                                        'returned' => 'bg-success',
                                                        default => 'bg-secondary'
                                                    };
                                                @endphp
                                                <span class="badge {{ $badgeClass }}">{{ ucfirst($issue->status) }}</span>
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
                        <div class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                            <p class="text-muted mb-3">Brak wydań w systemie.</p>
                            <a href="{{ route('equipment-issues.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Wydaj pierwszy sprzęt
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
