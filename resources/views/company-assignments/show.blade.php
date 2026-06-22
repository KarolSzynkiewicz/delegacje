<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Przypisanie: {{ $assignment->employee->full_name }} → {{ $assignment->company->name }}">
            <x-slot name="left">
                <x-ui.button variant="ghost" href="{{ route('company-assignments.index') }}" action="back">Powrót</x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button variant="ghost" href="{{ route('company-assignments.edit', $assignment) }}" routeName="company-assignments.edit" action="edit">Edytuj</x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row">
        <div class="col-lg-8">
            <x-ui.card label="Szczegóły przypisania">
                <x-ui.detail-list>
                    <x-ui.detail-item label="Pracownik:">
                        <a href="{{ route('employees.show', $assignment->employee) }}" class="text-primary text-decoration-none">
                            {{ $assignment->employee->full_name }}
                        </a>
                    </x-ui.detail-item>
                    <x-ui.detail-item label="Spółka:">
                        <a href="{{ route('companies.show', $assignment->company) }}" class="text-primary text-decoration-none">
                            {{ $assignment->company->name }}
                        </a>
                    </x-ui.detail-item>
                    <x-ui.detail-item label="Data od:">{{ $assignment->start_date->format('d.m.Y') }}</x-ui.detail-item>
                    <x-ui.detail-item label="Data do:">{{ $assignment->end_date ? $assignment->end_date->format('d.m.Y') : '-' }}</x-ui.detail-item>
                    <x-ui.detail-item label="Status:">
                        @php
                            if ($assignment->isCurrentlyActive()) {
                                $statusLabel = 'Aktywne';
                                $badgeVariant = 'success';
                            } elseif ($assignment->isPast()) {
                                $statusLabel = 'Zakończone';
                                $badgeVariant = 'accent';
                            } elseif ($assignment->isScheduled()) {
                                $statusLabel = 'Zaplanowane';
                                $badgeVariant = 'info';
                            } else {
                                $statusLabel = 'Nieznany';
                                $badgeVariant = 'accent';
                            }
                        @endphp
                        <x-ui.badge variant="{{ $badgeVariant }}">{{ $statusLabel }}</x-ui.badge>
                    </x-ui.detail-item>
                    @if($assignment->notes)
                        <x-ui.detail-item label="Notatki:" :full-width="true">{{ $assignment->notes }}</x-ui.detail-item>
                    @endif
                    <x-ui.detail-item label="Utworzono:">{{ $assignment->created_at->format('d.m.Y H:i') }}</x-ui.detail-item>
                    <x-ui.detail-item label="Zaktualizowano:">{{ $assignment->updated_at->format('d.m.Y H:i') }}</x-ui.detail-item>
                </x-ui.detail-list>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
