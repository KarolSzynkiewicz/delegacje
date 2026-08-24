<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$application->full_name ?: 'Proces #'.$application->id">
            <x-slot name="left">
                <a href="{{ route('recruitment-processes.index', request()->query()) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Lista
                </a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <livewire:recruitment-processes-table :process-id="$application->id" :key="'rp-show-'.$application->id" />
</x-app-layout>
