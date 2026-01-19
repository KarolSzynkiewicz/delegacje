<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Rotacja: {{ $employee->full_name }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('employees.rotations.index', $employee) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('employees.rotations.edit', [$employee, $rotation]) }}"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row">
        <div class="col-lg-8">
            <x-ui.card label="Szczegóły rotacji">
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong class="text-muted">Pracownik:</strong>
                            </div>
                            <div class="col-md-8">
                                <a href="{{ route('employees.show', $employee) }}" class="text-decoration-none">
                                    {{ $employee->full_name }}
                                </a>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong class="text-muted">Data rozpoczęcia:</strong>
                            </div>
                            <div class="col-md-8">
                                {{ $rotation->start_date->format('d.m.Y') }}
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong class="text-muted">Data zakończenia:</strong>
                            </div>
                            <div class="col-md-8">
                                {{ $rotation->end_date->format('d.m.Y') }}
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong class="text-muted">Status:</strong>
                            </div>
                            <div class="col-md-8">
                                @php
                                    $today = now()->toDateString();
                                    $isActive = $rotation->start_date->toDateString() <= $today && 
                                                $rotation->end_date->toDateString() >= $today;
                                    $isCompleted = $rotation->end_date->toDateString() < $today;
                                    $isScheduled = $rotation->start_date->toDateString() > $today;
                                    $isCancelled = $rotation->status === 'cancelled';
                                @endphp
                                @php
                                    $badgeVariant = match(true) {
                                        $isCancelled => 'danger',
                                        $isActive => 'success',
                                        $isCompleted => 'accent',
                                        $isScheduled => 'info',
                                        default => 'accent'
                                    };
                                    $badgeLabel = match(true) {
                                        $isCancelled => 'Anulowana',
                                        $isActive => 'Aktywna',
                                        $isCompleted => 'Zakończona',
                                        $isScheduled => 'Zaplanowana',
                                        default => 'Nieznany'
                                    };
                                @endphp
                                <x-ui.badge variant="{{ $badgeVariant }}">{{ $badgeLabel }}</x-ui.badge>
                            </div>
                        </div>

                        @if($rotation->notes)
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong class="text-muted">Uwagi:</strong>
                            </div>
                            <div class="col-md-8">
                                {{ $rotation->notes }}
                            </div>
                        </div>
                        @endif

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong class="text-muted">Utworzono:</strong>
                            </div>
                            <div class="col-md-8">
                                {{ $rotation->created_at->format('d.m.Y H:i') }}
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong class="text-muted">Zaktualizowano:</strong>
                            </div>
                            <div class="col-md-8">
                                {{ $rotation->updated_at->format('d.m.Y H:i') }}
                            </div>
                        </div>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
