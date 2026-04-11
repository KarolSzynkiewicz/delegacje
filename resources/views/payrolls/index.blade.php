<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Payroll">
            <x-slot name="left">
                <form action="{{ route('payrolls.recalculate-all') }}" method="POST" class="m-0 w-100 w-md-auto d-grid">
                    @csrf
                    <x-ui.button 
                        variant="warning" 
                        type="submit" 
                        action="refresh"
                        class="w-100 w-md-auto text-nowrap"
                        onclick="return confirm('Czy na pewno chcesz przeliczyć wszystkie payrolle?');"
                    >
                        Przelicz Wszystkie
                    </x-ui.button>
                </form>
            </x-slot>
            <x-slot name="right">
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('payrolls.generate-batch') }}"
                    routeName="payrolls.generate-batch"
                    action="filter"
                    class="w-100 w-md-auto text-nowrap"
                >
                    Wygeneruj dla wszystkich
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <livewire:payrolls-table />
</x-app-layout>
