<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Kara/Nagroda: {{ $adjustment->employee->full_name }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('adjustments.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('adjustments.edit', $adjustment) }}"
                    routeName="adjustments.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row">
        <div class="col-lg-8">
            <x-ui.card label="Szczegóły Kary/Nagrody">
                <x-ui.detail-list>
                    <x-ui.detail-item label="Pracownik:">
                        <a href="{{ route('employees.show', $adjustment->employee) }}" class="text-primary text-decoration-none">
                            {{ $adjustment->employee->full_name }}
                        </a>
                    </x-ui.detail-item>
                    <x-ui.detail-item label="Typ:">
                        <x-ui.badge variant="{{ $adjustment->type === 'bonus' ? 'success' : 'danger' }}">
                            {{ $adjustment->type === 'bonus' ? 'Nagroda' : 'Kara' }}
                        </x-ui.badge>
                    </x-ui.detail-item>
                            <x-ui.detail-item label="Kwota:">
                                <strong class="{{ $adjustment->amount >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($adjustment->amount, 2, ',', ' ') }} {{ $adjustment->currency }}
                                </strong>
                            </x-ui.detail-item>
                            <x-ui.detail-item label="Data:">{{ $adjustment->date->format('d.m.Y') }}</x-ui.detail-item>
                            @if($adjustment->notes)
                            <x-ui.detail-item label="Notatki:" :full-width="true">{{ $adjustment->notes }}</x-ui.detail-item>
                            @endif
                            <x-ui.detail-item label="Utworzono:">{{ $adjustment->created_at->format('d.m.Y H:i') }}</x-ui.detail-item>
                            <x-ui.detail-item label="Zaktualizowano:">{{ $adjustment->updated_at->format('d.m.Y H:i') }}</x-ui.detail-item>
                </x-ui.detail-list>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
