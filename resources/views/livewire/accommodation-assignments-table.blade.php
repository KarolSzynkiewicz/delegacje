<div>
    <x-ui.card class="mb-4">

            
            <div class="row g-3">
                <!-- Pracownik -->
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Pracownik</label>
                    <input type="text" wire:model.live.debounce.300ms="searchEmployee" 
                        placeholder="Szukaj pracownika..."
                        class="form-control form-control-sm">
                </div>

                <!-- Mieszkanie -->
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Mieszkanie</label>
                    <input type="text" wire:model.live.debounce.300ms="searchAccommodation" 
                        placeholder="Nazwa, adres..."
                        class="form-control form-control-sm">
                </div>

                <!-- Data od -->
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Data od</label>
                    <input type="date" wire:model.live="dateFrom" class="form-control form-control-sm">
                </div>

                <!-- Data do -->
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Data do</label>
                    <input type="date" wire:model.live="dateTo" class="form-control form-control-sm">
                </div>
            </div>
    </x-ui.card>

    <x-ui.card>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-start">Pracownik</th>
                            <th class="text-start">Mieszkanie</th>
                            <th class="text-start">Od - Do</th>
                            <th class="text-start">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assignments as $assignment)
                            <tr>
                                <td>
                                    <x-employee-cell :employee="$assignment->employee" />
                                </td>
                                <td>
                                    <a href="{{ route('accommodations.show', $assignment->accommodation) }}" class="text-primary text-decoration-none">
                                        {{ $assignment->accommodation->name }}
                                    </a>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        {{ $assignment->start_date->format('Y-m-d') }} - 
                                        {{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : '...' }}
                                    </small>
                                </td>
                                <td>
                                    <x-ui.action-buttons
                                        viewRoute="{{ route('accommodation-assignments.show', $assignment) }}"
                                        editRoute="{{ route('accommodation-assignments.edit', $assignment) }}"
                                        deleteRoute="{{ route('accommodation-assignments.destroy', $assignment) }}"
                                        deleteMessage="Czy na pewno chcesz usunąć to przypisanie mieszkania?"
                                    />
                                </td>
                            </tr>
                        @empty
                            <x-ui.empty-state 
                                icon="house-x"
                                message="Brak przypisań mieszkań"
                                :in-table="true"
                                colspan="4"
                            />
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($assignments->hasPages())
                <div class="mt-3">
                    {{ $assignments->links() }}
                </div>
            @endif
    </x-ui.card>
</div>
