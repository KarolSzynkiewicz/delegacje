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

    <x-ui.card class="mb-3">
        <form method="GET" action="{{ route('adjustments.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <x-ui.input type="select" name="payroll" label="Payroll">
                    <option value="all" {{ ($payrollFilter ?? request('payroll', 'all')) === 'all' ? 'selected' : '' }}>Wszystkie</option>
                    <option value="linked" {{ ($payrollFilter ?? request('payroll', 'all')) === 'linked' ? 'selected' : '' }}>Z payrollem</option>
                    <option value="unlinked" {{ ($payrollFilter ?? request('payroll', 'all')) === 'unlinked' ? 'selected' : '' }}>Bez payrollu</option>
                </x-ui.input>
            </div>
            <div class="col-md-3">
                <x-ui.input type="select" name="type" label="Typ">
                    <option value="all" {{ ($typeFilter ?? request('type', 'all')) === 'all' ? 'selected' : '' }}>Wszystkie</option>
                    <option value="penalty" {{ ($typeFilter ?? request('type', 'all')) === 'penalty' ? 'selected' : '' }}>Kara</option>
                    <option value="bonus" {{ ($typeFilter ?? request('type', 'all')) === 'bonus' ? 'selected' : '' }}>Nagroda</option>
                </x-ui.input>
            </div>
            <div class="col-md-3">
                <x-ui.input type="select" name="sort" label="Sortuj">
                    <option value="date" {{ ($sort ?? request('sort', 'date')) === 'date' ? 'selected' : '' }}>Data</option>
                    <option value="amount" {{ ($sort ?? request('sort', 'date')) === 'amount' ? 'selected' : '' }}>Kwota</option>
                    <option value="created_at" {{ ($sort ?? request('sort', 'date')) === 'created_at' ? 'selected' : '' }}>Utworzono</option>
                </x-ui.input>
            </div>
            <div class="col-md-2">
                <x-ui.input type="select" name="dir" label="Kierunek">
                    <option value="desc" {{ ($dir ?? request('dir', 'desc')) === 'desc' ? 'selected' : '' }}>Malejąco</option>
                    <option value="asc" {{ ($dir ?? request('dir', 'desc')) === 'asc' ? 'selected' : '' }}>Rosnąco</option>
                </x-ui.input>
            </div>
            <div class="col-md-1 d-flex gap-2">
                <x-ui.button variant="primary" type="submit" class="btn-sm">Filtruj</x-ui.button>
            </div>
        </form>
    </x-ui.card>

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
                            <th>Payroll</th>
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
                                    @if($adjustment->payroll_id)
                                        <x-ui.badge variant="success">Z payrollem</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="warning">Bez payrollu</x-ui.badge>
                                    @endif
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
