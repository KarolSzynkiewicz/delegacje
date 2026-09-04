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

    <x-ui.card class="mb-3">
        <form method="GET" action="{{ route('advances.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <x-ui.input type="select" name="payroll" label="Payroll">
                    <option value="all" {{ ($payrollFilter ?? request('payroll', 'all')) === 'all' ? 'selected' : '' }}>Wszystkie</option>
                    <option value="linked" {{ ($payrollFilter ?? request('payroll', 'all')) === 'linked' ? 'selected' : '' }}>Z payrollem</option>
                    <option value="unlinked" {{ ($payrollFilter ?? request('payroll', 'all')) === 'unlinked' ? 'selected' : '' }}>Bez payrollu</option>
                </x-ui.input>
            </div>
            <div class="col-md-2">
                <x-ui.button variant="primary" type="submit" class="btn-sm">Filtruj</x-ui.button>
            </div>
        </form>
    </x-ui.card>

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
                            <th>Payroll</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                            <tbody>
                                @foreach ($advances as $advance)
                                    <tr>
                                        <td>
                                            <x-employee-cell :employee="$advance->employee"  />
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
                                            @if($advance->payroll_id)
                                                <x-ui.badge variant="success">Z payrollem</x-ui.badge>
                                            @else
                                                <x-ui.badge variant="warning">Bez payrollu</x-ui.badge>
                                            @endif
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
                            <x-ui.pagination :paginator="$advances" />
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
