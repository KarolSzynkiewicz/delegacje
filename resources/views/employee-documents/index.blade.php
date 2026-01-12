<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-semibold fs-4 mb-0">
            Dokumenty Pracowników
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            @livewire('employee-documents-grouped')
        </div>
    </div>
</x-app-layout>
