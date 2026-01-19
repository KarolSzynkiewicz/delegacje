<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Payroll">
            <x-slot name="right">
                <div class="d-flex gap-2">
                    <x-ui.button 
                        variant="primary" 
                        href="{{ route('payrolls.generate-batch') }}"
                        routeName="payrolls.generate-batch"
                        action="filter"
                    >
                        Generuj Payroll
                    </x-ui.button>
                    <form action="{{ route('payrolls.recalculate-all') }}" method="POST" class="d-inline">
                        @csrf
                        <x-ui.button 
                            variant="warning" 
                            type="submit" 
                            action="refresh"
                            onclick="return confirm('Czy na pewno chcesz przeliczyć wszystkie payrolle?');"
                        >
                            Przelicz Wszystkie
                        </x-ui.button>
                    </form>
                    <x-ui.button 
                        variant="ghost" 
                        href="{{ route('payrolls.create') }}"
                        routeName="payrolls.create"
                        action="create"
                    >
                        Nowy Payroll
                    </x-ui.button>
                </div>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <livewire:payrolls-table />
</x-app-layout>
