<x-app-layout>
    //review
    <x-slot name="header">
        <x-ui.page-header title="Szczegóły Przypisania Mieszkania">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route(name: 'employees.show', parameters: $accommodationAssignment->employee_id) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route(name: 'accommodation-assignments.edit', parameters: $accommodationAssignment) }}"
                    routeName="accommodation-assignments.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <x-ui.card label="Szczegóły Przypisania Mieszkania">
                <x-ui.detail-list>
                    <x-ui.detail-item label="Pracownik:">
                        <a href="{{ route(name: 'employees.show', parameters: $accommodationAssignment->employee) }}" class="text-primary text-decoration-none">
                            {{ $accommodationAssignment->employee->full_name }}
                        </a>
                    </x-ui.detail-item>
                    <x-ui.detail-item label="Mieszkanie:">
                        <a href="{{ route(name: 'accommodations.show', parameters: $accommodationAssignment->accommodation) }}" class="text-primary text-decoration-none">
                            {{ $accommodationAssignment->accommodation->name }} - {{ $accommodationAssignment->accommodation->city }}
                        </a>
                    </x-ui.detail-item>
                    <x-ui.detail-item label="Data Rozpoczęcia:">{{ $accommodationAssignment->start_date->format('Y-m-d') }}</x-ui.detail-item>
                    <x-ui.detail-item label="Data Zakończenia:">{{ $accommodationAssignment->end_date ? $accommodationAssignment->end_date->format('Y-m-d') : 'Bieżące' }}</x-ui.detail-item>
                    @if($accommodationAssignment->notes)
                    <x-ui.detail-item label="Uwagi:" :full-width="true">{{ $accommodationAssignment->notes }}</x-ui.detail-item>
                    @endif
                </x-ui.detail-list>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
