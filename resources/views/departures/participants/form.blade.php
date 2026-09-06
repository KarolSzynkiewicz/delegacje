<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$isEdit ? 'Edycja uczestnika wyjazdu' : 'Dopisz uczestnika do wyjazdu'">
            <x-slot name="left">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('departures.show', $departure) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="container-fluid">
        @if(session('success'))
            <x-ui.alert variant="success" title="Sukces" dismissible class="mb-3">
                {{ session('success') }}
            </x-ui.alert>
        @endif

        @if(session('error'))
            <x-ui.alert variant="danger" title="Błąd" dismissible class="mb-3">
                {{ session('error') }}
            </x-ui.alert>
        @endif

        <p class="small text-muted mb-4">
            Te same ekrany co przy tworzeniu wyjazdu: kto jest w bazie i ma rotację, kto mieszka w którym domu, ile miejsc mają auta — i kalendarz na każdy dzień.
            Daty wyjazdu, auto nagłówka i trasa zostają jak są. Zapis dotyczy tylko tej osoby i jej przypisań.
        </p>

        <livewire:departure-participant-planner
            :departure-id="$departure->id"
            :employee-id="$employee?->id"
            :key="'participant-planner-'.$departure->id.'-'.($employee?->id ?? 'new')"
        />

        <livewire:employee-assignment-modal
            wire:key="employee-assignment-modal"
        />
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    @endpush
</x-app-layout>
