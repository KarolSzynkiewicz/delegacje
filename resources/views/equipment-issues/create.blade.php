@php $mode = $mode ?? 'returnable'; @endphp
<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$mode === 'given' ? 'Wydaj bezzwrotnie' : 'Wydaj do zwrotu'">
            <x-slot name="left">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('equipment.tab.issues') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                @include('equipment._warehouse-switcher', [
                    'warehouses' => $warehouses,
                    'current' => $warehouse,
                    'routeName' => 'equipment-issues.create',
                    'keep' => ['mode' => $mode === 'given' ? 'given' : null],
                ])
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card :label="($mode === 'given' ? 'Wydanie bezzwrotne' : 'Wydanie do zwrotu').': '.$warehouse->display_name">
        <livewire:warehouse-issue-form
            :warehouse="$warehouse"
            :mode="$mode"
            :key="'issue-form-'.$warehouse->id.'-'.$mode"
        />
    </x-ui.card>
</x-app-layout>
