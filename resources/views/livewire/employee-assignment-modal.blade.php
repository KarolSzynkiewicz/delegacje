<div wire:key="employee-assignment-modal-root">
    @if($show && $employee && $project && $role && $arrivalDate)
        <!-- Debug info -->
        @if(config('app.debug'))
            <div style="position: fixed; top: 10px; right: 10px; background: rgba(255,0,0,0.8); color: white; padding: 10px; z-index: 10000; font-size: 12px;">
                Modal State: show={{ $show ? 'true' : 'false' }}<br>
                Employee: {{ is_array($employee) ? ($employee['id'] ?? 'none') : $employee }}<br>
                Project: {{ is_array($project) ? ($project['id'] ?? 'none') : $project }}<br>
                Role: {{ is_array($role) ? ($role['id'] ?? 'none') : $role }}
            </div>
        @endif
        <div class="modal-overlay" 
             style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(4px); z-index: 9998; display: flex !important; align-items: center; justify-content: center; padding: 2rem;"
             onclick="if (event.target === this) { $wire.close(); }"
             wire:key="employee-modal-overlay-{{ is_array($employee) ? ($employee['id'] ?? 'none') : $employee }}"
        >
            <div class="modal-container" 
                 style="position: relative; max-width: 900px; width: 100%; max-height: 90vh; overflow-y: auto; z-index: 9999;"
                 onclick="event.stopPropagation();"
            >
                <x-ui.card style="margin: 0;">
                    <!-- Header -->
                    <div class="d-flex align-items-start justify-content-between mb-4 pb-3" style="border-bottom: 1px solid var(--glass-border);">
                        <div>
                            <h5 class="fw-semibold mb-1 text-main">
                                Przypisz: {{ is_array($employee) ? ($employee['full_name'] ?? 'N/A') : 'N/A' }}
                            </h5>
                            <div class="small text-muted">
                                @php
                                    $projectObj = is_numeric($project) ? \App\Models\Project::find($project) : null;
                                    $roleObj = is_numeric($role) ? \App\Models\Role::find($role) : null;
                                    $projectName = $projectObj ? $projectObj->name : 'N/A';
                                    $roleName = $roleObj ? $roleObj->name : 'N/A';
                                @endphp
                                {{ $projectName }} - {{ $roleName }}
                            </div>
                        </div>
                        <button 
                            type="button" 
                            class="btn btn-link text-muted p-0"
                            wire:click="close"
                            style="font-size: 1.5rem; line-height: 1; text-decoration: none;"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    
                    <!-- Body -->
                    <div class="modal-body-content">
                        <p class="text-muted small mb-3">
                            Wybierz zakres dat przypisania. Kliknij datę początkową, a następnie datę końcową.
                            Wyszarzone dni są niedostępne (brak zapotrzebowania, dokumentów, rotacji lub już przypisany).
                        </p>
                        
                        @if($selectedStartDate)
                            <x-ui.alert variant="info" class="mb-3">
                                @if($selectedEndDate && $selectedStartDate !== $selectedEndDate)
                                    <strong>Wybrany zakres:</strong> 
                                    {{ \Carbon\Carbon::parse($selectedStartDate)->format('d.m.Y') }} - 
                                    {{ \Carbon\Carbon::parse($selectedEndDate)->format('d.m.Y') }}
                                    <br>
                                    <small>Kliknij inną datę aby zmienić zakres końcowy</small>
                                @else
                                    <strong>Wybrana data początkowa:</strong> 
                                    {{ \Carbon\Carbon::parse($selectedStartDate)->format('d.m.Y') }}
                                    <br>
                                    <small>Kliknij datę końcową aby wybrać zakres</small>
                                @endif
                            </x-ui.alert>
                        @else
                            <x-ui.alert variant="info" class="mb-3">
                                <small>Kliknij datę początkową przypisania</small>
                            </x-ui.alert>
                        @endif
                        
                        @php
                            $arrivalDateCarbon = \Carbon\Carbon::parse($arrivalDate);
                        @endphp
                        
                        @if(empty($employeeAvailability))
                            <x-ui.alert variant="warning" class="mb-3">
                                <strong>Brak danych dostępności.</strong><br>
                                <small>Sprawdź czy:</small>
                                <ul class="small mb-0 mt-2">
                                    <li>Pracownik ma wszystkie wymagane dokumenty</li>
                                    <li>Pracownik ma aktywną rotację</li>
                                    <li>Projekt ma zapotrzebowanie na rolę "{{ is_numeric($role) ? (\App\Models\Role::find($role)->name ?? '') : '' }}"</li>
                                </ul>
                            </x-ui.alert>
                        @else
                            @php
                                $availableDays = collect($employeeAvailability)->filter(fn($day) => $day['can_assign'] ?? false)->count();
                                $totalDays = count($employeeAvailability);
                            @endphp
                            @if($availableDays === 0)
                                <x-ui.alert variant="warning" class="mb-3">
                                    <strong>Brak dostępnych dni.</strong> Pracownik nie może być przypisany w żadnym z 30 dni od daty przybycia.
                                </x-ui.alert>
                            @else
                                <x-ui.alert variant="info" class="mb-3">
                                    <strong>Dostępne dni:</strong> {{ $availableDays }} z {{ $totalDays }} dni.
                                </x-ui.alert>
                            @endif
                        @endif
                        
                        <x-ui.cal 
                            wire:key="employee-calendar-{{ is_array($employee) ? ($employee['id'] ?? 'none') : $employee }}-{{ is_array($project) ? ($project['id'] ?? 'none') : $project }}-{{ is_array($role) ? ($role['id'] ?? 'none') : $role }}"
                            :startDate="$arrivalDateCarbon"
                            :days="30"
                            :availability="$employeeAvailability"
                            :selectedStartDate="$selectedStartDate"
                            :selectedEndDate="$selectedEndDate"
                            onDateClick="selectDate"
                        />
                    </div>
                    
                    <!-- Footer -->
                    <div class="d-flex align-items-center justify-content-end gap-2 mt-4 pt-3" style="border-top: 1px solid var(--glass-border);">
                        <x-ui.button variant="ghost" wire:click="close">Anuluj</x-ui.button>
                        @if($selectedStartDate)
                            <x-ui.button variant="primary" wire:click="confirmAssignment">
                                Potwierdź przypisanie
                            </x-ui.button>
                        @endif
                    </div>
                </x-ui.card>
            </div>
        </div>
    @else
        <!-- Hidden when modal is not shown - ensures root tag always has content -->
        <div style="display: none;"></div>
    @endif
</div>
