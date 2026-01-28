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

    <!-- Filtry -->
    <x-ui.card class="mb-4">
        <form method="GET" action="{{ route('equipment-issues.index') }}" id="filter-form">
            <div class="row g-3">
                <!-- Status -->
                <div class="col-md-4">
                    <label class="form-label small">
                        <i class="bi bi-check-circle me-1"></i> Status
                    </label>
                    <select name="status" class="form-control" onchange="this.form.submit()">
                        <option value="">Wszystkie statusy</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Pracownik -->
                <div class="col-md-4">
                    <label class="form-label small">
                        <i class="bi bi-person me-1"></i> Pracownik
                    </label>
                    <select name="employee_id" class="form-control" onchange="this.form.submit()">
                        <option value="">Wszyscy pracownicy</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                {{ $employee->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Sprzęt -->
                <div class="col-md-4">
                    <label class="form-label small">
                        <i class="bi bi-tools me-1"></i> Sprzęt
                    </label>
                    <select name="equipment_id" class="form-control" onchange="this.form.submit()">
                        <option value="">Wszystkie sprzęty</option>
                        @foreach($equipmentList as $equipment)
                            <option value="{{ $equipment->id }}" {{ request('equipment_id') == $equipment->id ? 'selected' : '' }}>
                                {{ $equipment->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>

        @if(request('status') || request('employee_id') || request('equipment_id'))
            <div class="mt-3 pt-3 border-top">
                <a href="{{ route('equipment-issues.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                </a>
            </div>
        @endif
    </x-ui.card>

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
                                                    'damaged' => 'danger',
                                                    'lost' => 'warning',
                                                    default => 'info'
                                                };
                                                $statusLabels = [
                                                    'issued' => 'Wydany',
                                                    'returned' => 'Zwrócony',
                                                    'damaged' => 'Uszkodzony',
                                                    'lost' => 'Zgubiony',
                                                ];
                                            @endphp
                                            <x-ui.badge variant="{{ $badgeVariant }}">{{ $statusLabels[$issue->status] ?? ucfirst($issue->status) }}</x-ui.badge>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <x-view-button href="{{ route('equipment-issues.show', $issue) }}" />
                                                @if($issue->status === 'issued' && $issue->equipment->returnable)
                                                    <x-ui.button variant="success" href="{{ route('equipment-issues.return', $issue) }}" class="btn-sm" title="Zwróć/Zgłoś">
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
                            <x-ui.pagination :paginator="$issues" />
                        </div>
                    @endif
                @else
                    <x-ui.empty-state 
                        icon="inbox" 
                        :message="request('status') || request('employee_id') || request('equipment_id') ? 'Brak wydań spełniających kryteria wyszukiwania' : 'Brak wydań w systemie.'"
                    >
                        @if(!request('status') && !request('employee_id') && !request('equipment_id'))
                            <x-ui.button 
                                variant="primary" 
                                href="{{ route('equipment-issues.create') }}"
                                action="create"
                            >
                                Wydaj pierwszy sprzęt
                            </x-ui.button>
                        @else
                            <a href="{{ route('equipment-issues.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                            </a>
                        @endif
                    </x-ui.empty-state>
                @endif
    </x-ui.card>
</x-app-layout>
