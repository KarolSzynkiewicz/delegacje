@php
    $usagePercentage = $accommodationData['usage_percentage'] ?? 0;
    $progressVariant = $usagePercentage == 100 ? 'success' : ($usagePercentage >= 70 ? 'warning' : 'danger');
    $progressColor   = $usagePercentage == 100 ? '#10b981' : ($usagePercentage >= 70 ? '#f59e0b' : '#ef4444');

    $accType = $accommodation->type ?? '';
    if ($accType === 'własny') {
        $leaseCaption = 'Mieszkanie własne';
    } elseif ($accType === 'wynajmowany' && $accommodation->lease_end_date) {
        $days = (int) now()->startOfDay()->diffInDays($accommodation->lease_end_date->copy()->startOfDay(), false);
        if ($days < 0) {
            $leaseCaption = 'Najem zakończony';
        } elseif ($days === 0) {
            $leaseCaption = 'Ostatni dzień najmu';
        } else {
            $leaseCaption = 'Koniec najmu: ' . $days . ' ' . ($days === 1 ? 'dzień' : 'dni');
        }
    } elseif ($accType === 'wynajmowany') {
        $leaseCaption = 'Wynajem — brak daty końca';
    } else {
        $leaseCaption = null;
    }

    $accommodationEmployeeIds = collect($accommodationData['assignments'] ?? [])->pluck('employee_id')->unique()->filter();
    $otherProjects = collect();
    if ($accommodationEmployeeIds->isNotEmpty()) {
        $pa = collect();
        foreach ($accommodationEmployeeIds as $eid) {
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
        <a href="{{ route('accommodations.show', $accommodation) }}" class="d-block text-decoration-none" style="flex-shrink:0;">
            <div style="height:148px; position:relative; background: linear-gradient(135deg, rgba(16,185,129,0.1) 0%, rgba(5,150,105,0.05) 100%); display:flex; align-items:center; justify-content:center;">
                @if($accommodation->image_path)
                    <img src="{{ $accommodation->image_url }}"
                         alt="{{ $accommodation->name }}"
                         style="max-width:100%; max-height:100%; width:auto; height:auto; object-fit:contain; display:block;"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div style="display:none; width:100%; height:100%; align-items:center; justify-content:center; position:absolute; top:0; left:0;">
                        <i class="bi bi-house" style="font-size:4rem; color:rgba(16,185,129,0.3);"></i>
                    </div>
                @else
                    <i class="bi bi-house" style="font-size:4.5rem; color:rgba(16,185,129,0.3);"></i>
                @endif

                {{-- Nakładka: zapełnienie --}}
                <div style="position:absolute; top:8px; right:8px; background:rgba(0,0,0,0.55); color:white; font-size:0.72rem; font-weight:700; padding:2px 8px; border-radius:20px; backdrop-filter:blur(4px); line-height:1.6;">
                    {{ $accommodationData['usage'] }}
                </div>

                {{-- Nakładka: status najmu --}}
                @if($leaseCaption)
                    <div style="position:absolute; bottom:6px; left:8px; right:8px;">
                        <span style="background:rgba(0,0,0,0.55); color:rgba(255,255,255,0.8); font-size:0.7rem; padding:2px 8px; border-radius:10px; backdrop-filter:blur(4px);">
                            {{ $leaseCaption }}
                        </span>
                    </div>
                @endif
            </div>
        </a>

        {{-- Treść karty --}}
        <div style="padding: 12px 14px; flex:1; display:flex; flex-direction:column; gap:10px;">

            {{-- Nagłówek: nazwa + progress --}}
            <div>
                <div class="d-flex justify-content-between align-items-baseline mb-1 gap-2">
                    <a href="{{ route('accommodations.show', $accommodation) }}" class="fw-bold text-decoration-none text-truncate" style="font-size:0.88rem; color:var(--text-main,#f1f5f9);">
                        {{ $accommodation->name }}
                    </a>
                    <span style="font-size:0.72rem; font-weight:700; color:{{ $progressColor }}; flex-shrink:0;">{{ $usagePercentage }}%</span>
                </div>
                <x-ui.progress value="{{ $usagePercentage }}" max="100" variant="{{ $progressVariant }}" />
            </div>

            {{-- Lista osób --}}
            @if(isset($accommodationData['assignments']) && $accommodationData['assignments']->isNotEmpty())
                <ul class="list-unstyled mb-0" style="display:flex; flex-direction:column; gap:3px;">
                    @foreach($accommodationData['assignments'] as $assignment)
                        <li>
                            <a href="{{ route('accommodation-assignments.show', $assignment) }}"
                               class="text-decoration-none d-flex align-items-center gap-2"
                               style="padding:3px 7px; border-radius:6px; font-size:0.82rem; background:rgba(16,185,129,0.08);">
                                <i class="bi bi-person" style="font-size:0.75rem; color:rgba(16,185,129,0.7); flex-shrink:0;"></i>
                                <span style="color:var(--text-main,#f1f5f9);">{{ $assignment->employee->full_name }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="small text-muted mb-0 fst-italic">Brak przypisanych osób</p>
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
