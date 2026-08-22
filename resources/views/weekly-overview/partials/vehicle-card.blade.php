@php
    $usagePercentage = $vehicleData['usage_percentage'] ?? 0;
    $progressVariant = $usagePercentage == 100 ? 'success' : ($usagePercentage >= 70 ? 'warning' : 'danger');
    $progressColor   = $usagePercentage == 100 ? '#10b981' : ($usagePercentage >= 70 ? '#f59e0b' : '#ef4444');

    $vehicleEmployeeIds = collect($vehicleData['assignments'] ?? [])->pluck('employee_id')->unique()->filter();
    $otherProjects = collect();
    if ($vehicleEmployeeIds->isNotEmpty()) {
        $pa = collect();
        foreach ($vehicleEmployeeIds as $eid) {
            if ($preloadedProjectAssignments->has($eid)) {
                $pa = $pa->merge($preloadedProjectAssignments->get($eid));
            }
        }
        $otherProjects = $pa->pluck('project')->filter()->unique('id')
            ->filter(fn($p) => $p->id !== $project->id)->values();
    }
@endphp

<div class="col-12 col-md-6 col-lg-3">
    <div class="h-100 d-flex flex-column" style="border-radius: 12px; overflow: hidden; border: 1px solid var(--glass-border, rgba(255,255,255,0.1)); background: var(--bg-card, #1e293b);">

        {{-- Baner ze zdjęciem --}}
        <a href="{{ route('vehicles.show', $vehicleData['vehicle']) }}" class="d-block text-decoration-none" style="flex-shrink:0;">
            <div class="wo-media-banner" style="height:148px; position:relative; background: linear-gradient(135deg, rgba(6,182,212,0.1) 0%, rgba(14,165,233,0.05) 100%); display:flex; align-items:center; justify-content:center;">
                @if($vehicleData['vehicle']->image_path)
                    <img src="{{ $vehicleData['vehicle']->image_url }}"
                         alt="{{ $vehicleData['vehicle_name'] }}"
                         style="max-width:100%; max-height:100%; width:auto; height:auto; object-fit:contain; display:block;"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div style="display:none; width:100%; height:100%; align-items:center; justify-content:center; position:absolute; top:0; left:0;">
                        <i class="bi bi-car-front" style="font-size:4rem; color:rgba(6,182,212,0.3);"></i>
                    </div>
                @else
                    <i class="bi bi-car-front" style="font-size:4.5rem; color:rgba(6,182,212,0.3);"></i>
                @endif

                {{-- Nakładka: zapełnienie --}}
                <div style="position:absolute; top:8px; right:8px; background:rgba(0,0,0,0.6); color:white; font-size:0.72rem; font-weight:700; padding:2px 8px; border-radius:20px; backdrop-filter:blur(4px); line-height:1.6;">
                    {{ $vehicleData['usage'] }}
                </div>
            </div>
        </a>

        {{-- Treść karty --}}
        <div style="padding: 12px 14px; flex:1; display:flex; flex-direction:column; gap:10px;">

            {{-- Nagłówek: nazwa + progress --}}
            <div>
                <div class="d-flex justify-content-between align-items-baseline mb-1 gap-2">
                    <a href="{{ route('vehicles.show', $vehicleData['vehicle']) }}" class="fw-bold text-decoration-none text-truncate" style="font-size:0.88rem; color:var(--text-main,#f1f5f9);">
                        {{ $vehicleData['vehicle_name'] }}
                    </a>
                    <span style="font-size:0.72rem; font-weight:700; color:{{ $progressColor }}; flex-shrink:0;">{{ $usagePercentage }}%</span>
                </div>
                <x-ui.progress value="{{ $usagePercentage }}" max="100" variant="{{ $progressVariant }}" />
            </div>

            {{-- Ostrzeżenia dokumentów (OC, przegląd) --}}
            <x-weekly-overview.vehicle-doc-captions :vehicle="$vehicleData['vehicle']" />

            {{-- Lista osób --}}
            @if(isset($vehicleData['assignments']) && $vehicleData['assignments']->isNotEmpty())
                <ul class="list-unstyled mb-0" style="display:flex; flex-direction:column; gap:3px;">
                    @foreach($vehicleData['assignments'] as $assignment)
                        @php
                            $pos = $assignment->position ?? \App\Enums\VehiclePosition::PASSENGER;
                            $posVal = $pos instanceof \App\Enums\VehiclePosition ? $pos->value : $pos;
                            $isDriver = $posVal === 'driver';
                        @endphp
                        <li>
                            <a href="{{ route('vehicle-assignments.show', $assignment) }}"
                               class="text-decoration-none d-flex align-items-center gap-2"
                               style="padding:3px 7px; border-radius:6px; font-size:0.82rem; background:{{ $isDriver ? 'rgba(16,185,129,0.1)' : 'rgba(255,255,255,0.04)' }};">
                                <i class="bi {{ $isDriver ? 'bi-car-front-fill' : 'bi-person' }}"
                                   style="font-size:0.75rem; color:{{ $isDriver ? '#10b981' : 'rgba(148,163,184,0.7)' }}; flex-shrink:0;"></i>
                                <span style="color:{{ $isDriver ? '#10b981' : 'var(--text-main,#f1f5f9)' }}; font-weight:{{ $isDriver ? '600' : '400' }};">
                                    {{ $assignment->employee->full_name }}
                                </span>
                                @if($isDriver)
                                    <span style="margin-left:auto; font-size:0.65rem; background:rgba(16,185,129,0.2); color:#10b981; padding:1px 6px; border-radius:10px; white-space:nowrap; flex-shrink:0;">kierowca</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="small text-muted mb-0 fst-italic">Brak przypisanych osób</p>
            @endif

            {{-- Zdarzenia --}}
            @php
                $hasReturn    = isset($vehicleData['return_trip']) && $vehicleData['return_trip'] !== null;
                $hasDeparture = ($vehicleData['departure_events'] ?? collect())->isNotEmpty();
                $hasTransfer  = ($vehicleData['transfer_events'] ?? collect())->isNotEmpty();
            @endphp
            @if($hasReturn || $hasDeparture || $hasTransfer)
                <div style="border-top:1px solid rgba(255,255,255,0.07); padding-top:7px; display:flex; flex-direction:column; gap:4px;">
                    @if($hasReturn)
                        @php $returnTrip = $vehicleData['return_trip']; @endphp
                        <a href="{{ route('return-trips.show', $returnTrip) }}" class="text-decoration-none d-flex align-items-center gap-2" style="font-size:0.8rem; color:var(--text-main,#f1f5f9);">
                            <i class="bi bi-arrow-return-left" style="color:#f59e0b; flex-shrink:0;"></i>
                            Zjazd: {{ ($returnTrip->end_date ?? $returnTrip->event_date)->format('d.m.Y') }}
                        </a>
                    @endif
                    @foreach(($vehicleData['departure_events'] ?? collect()) as $depEvent)
                        <a href="{{ route('departures.show', $depEvent) }}" class="text-decoration-none d-flex align-items-center gap-2" style="font-size:0.8rem; color:var(--text-main,#f1f5f9);">
                            <i class="bi bi-box-arrow-right" style="color:#06b6d4; flex-shrink:0;"></i>
                            Wyjazd: {{ $depEvent->event_date->format('d.m.Y') }}
                        </a>
                    @endforeach
                    @foreach(($vehicleData['transfer_events'] ?? collect()) as $transferEvent)
                        @php $fromName = $transferEvent->fromLocation?->name; $toName = $transferEvent->toLocation?->name; @endphp
                        <a href="{{ route('transfers.show', $transferEvent) }}" class="text-decoration-none d-flex align-items-center gap-2" style="font-size:0.8rem; color:var(--text-main,#f1f5f9);">
                            <i class="bi bi-arrow-left-right" style="color:#3b82f6; flex-shrink:0;"></i>
                            <span>Transfer: {{ $transferEvent->event_date->format('d.m.Y') }}@if($fromName || $toName) &middot; {{ $fromName ?? '?' }} → {{ $toName ?? '?' }}@endif</span>
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Obsługuje również --}}
            @if($otherProjects->isNotEmpty())
                <div style="border-top:1px solid rgba(255,255,255,0.07); padding-top:7px; margin-top:auto;">
                    <div style="font-size:0.68rem; color:rgba(148,163,184,0.6); text-transform:uppercase; letter-spacing:0.05em; margin-bottom:5px;">Obsługuje też:</div>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($otherProjects as $otherProject)
                            <x-ui.clickable-badge variant="info" route="projects.show" :routeParams="['project' => $otherProject]" class="small">
                                {{ $otherProject->name }}
                            </x-ui.clickable-badge>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
