<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Szczegóły Oceny</h2>
            <div class="d-flex gap-2">
                <x-ui.button variant="ghost" href="{{ route('employee-evaluations.edit', $employeeEvaluation) }}">
                    <i class="bi bi-pencil"></i> Edytuj
                </x-ui.button>
                <x-ui.button variant="ghost" href="{{ route('employee-evaluations.index') }}">
                    <i class="bi bi-arrow-left"></i> Powrót
                </x-ui.button>
            </div>
        </div>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Szczegóły Oceny">
                <x-ui.detail-list>
                    <x-ui.detail-item label="Pracownik:">
                        <x-employee-cell :employee="$employeeEvaluation->employee" />
                    </x-ui.detail-item>
                    
                    <x-ui.detail-item label="Data utworzenia:">
                        {{ $employeeEvaluation->created_at->format('Y-m-d H:i') }}
                    </x-ui.detail-item>
                    
                    <x-ui.detail-item label="Oceniający:">
                        {{ $employeeEvaluation->createdBy->name ?? '-' }}
                    </x-ui.detail-item>
                    
                    <x-ui.detail-item label="Zaangażowanie:">
                        <x-ui.badge variant="{{ $employeeEvaluation->engagement >= 7 ? 'success' : ($employeeEvaluation->engagement >= 4 ? 'warning' : 'danger') }}">
                            {{ $employeeEvaluation->engagement }}/10
                        </x-ui.badge>
                    </x-ui.detail-item>
                    
                    <x-ui.detail-item label="Umiejętności:">
                        <x-ui.badge variant="{{ $employeeEvaluation->skills >= 7 ? 'success' : ($employeeEvaluation->skills >= 4 ? 'warning' : 'danger') }}">
                            {{ $employeeEvaluation->skills }}/10
                        </x-ui.badge>
                    </x-ui.detail-item>
                    
                    <x-ui.detail-item label="Porządek:">
                        <x-ui.badge variant="{{ $employeeEvaluation->orderliness >= 7 ? 'success' : ($employeeEvaluation->orderliness >= 4 ? 'warning' : 'danger') }}">
                            {{ $employeeEvaluation->orderliness }}/10
                        </x-ui.badge>
                    </x-ui.detail-item>
                    
                    <x-ui.detail-item label="Zachowanie:">
                        <x-ui.badge variant="{{ $employeeEvaluation->behavior >= 7 ? 'success' : ($employeeEvaluation->behavior >= 4 ? 'warning' : 'danger') }}">
                            {{ $employeeEvaluation->behavior }}/10
                        </x-ui.badge>
                    </x-ui.detail-item>
                    
                    <x-ui.detail-item label="Średnia ocena:">
                        <strong class="fs-5">{{ number_format($employeeEvaluation->average_score, 2) }}/10</strong>
                    </x-ui.detail-item>
                    
                    @if($employeeEvaluation->notes)
                        <x-ui.detail-item label="Uwagi:">
                            <div class="p-3 bg-light rounded">
                                {{ $employeeEvaluation->notes }}
                            </div>
                        </x-ui.detail-item>
                    @endif
                </x-ui.detail-list>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
