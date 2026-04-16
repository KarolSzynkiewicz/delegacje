{{-- Wspólna siatka miejsc: planer wyjazdu (create-v2), zjazd, katalog /2 --}}
@php
    $titleSuffix = $vehicle ? ' – '.$vehicle->brand.' '.$vehicle->model : '';
@endphp

<style>
    .lvs-seat {
        width: 130px; min-height: 130px; height: auto;
        border-radius: 14px;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        gap: 6px; padding: 10px;
        transition: all .15s ease;
        position: relative;
    }
    .lvs-seat-driver {
        background: rgba(99,102,241,0.13);
        border: 1.5px solid rgba(99,102,241,0.4);
        width: 150px; min-height: 145px;
    }
    .lvs-seat-driver:hover { border-color: rgba(99,102,241,0.7); background: rgba(99,102,241,0.2); }
    .lvs-seat-occupied {
        background: rgba(59,130,246,0.10);
        border: 1.5px solid rgba(59,130,246,0.55);
        box-shadow:
            0 10px 26px rgba(0,0,0,0.28),
            inset 0 1px 0 rgba(255,255,255,0.08);
        cursor: grab;
    }
    .lvs-seat-occupied:hover {
        border-color: rgba(59,130,246,0.55);
        background: rgba(59,130,246,0.13);
        box-shadow:
            0 12px 30px rgba(0,0,0,0.32),
            0 0 0 3px rgba(59,130,246,0.14),
            inset 0 1px 0 rgba(255,255,255,0.10);
    }
    .lvs-seat-occupied:active { cursor: grabbing; }
    .lvs-seat-empty { background: rgba(255,255,255,0.015); border: 1.5px dashed rgba(255,255,255,0.12); }
    .lvs-seat-drag-over { border-color: rgba(99,102,241,0.8) !important; background: rgba(99,102,241,0.18) !important; }
    .lvs-label { font-size: .7rem; text-transform: uppercase; letter-spacing: .05em; font-weight: 600; opacity: .55; }
    .lvs-seat-icon { font-size: 1.6rem; opacity: .25; line-height: 1; }
    .lvs-avatar { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
    .lvs-avatar-placeholder { width: 36px; height: 36px; border-radius: 50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.75rem; }
</style>

<div class="mt-3 pt-3" style="border-top: 1px solid rgba(255,255,255,0.08);">
    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
        <i class="bi bi-car-front text-muted"></i>
        <span class="small fw-semibold">Miejsca w pojeździe{{ $titleSuffix }}</span>
        @if(! empty($vehicleSeats))
            @if($isMissingDriver)
                <span class="badge rounded-pill" style="background:rgba(245,158,11,0.16); color:#fcd34d; font-size:.7rem; border:1px solid rgba(245,158,11,0.35);">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Brak kierowcy
                </span>
            @elseif($isOverCapacity)
                <span class="badge rounded-pill" style="background:rgba(239,68,68,0.18); color:#fca5a5; font-size:.7rem; border:1px solid rgba(239,68,68,0.35);">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $totalTripPeople }}/{{ $capacity }} — za dużo osób!
                </span>
            @else
                <span class="badge rounded-pill" style="background:rgba(255,255,255,0.08); color:#94a3b8; font-size:.7rem;">
                    {{ $totalTripPeople }}/{{ $capacity }} zajęte
                </span>
            @endif
        @endif
    </div>

    @if(empty($vehicleSeats))
        <div class="text-muted small d-flex align-items-center gap-2" style="opacity:.5;">
            <div class="spinner-border spinner-border-sm" role="status" style="width:14px; height:14px; border-width:2px;"></div>
            Ładowanie miejsc…
        </div>
    @else
        <div
            class="d-flex flex-wrap gap-2 align-items-start"
            @if($interactive)
                x-data="{
                    dragging: null,
                    startDrag(id) { this.dragging = id; },
                    overDriver: false,
                    dropOnDriver() {
                        if (this.dragging) { $wire.assignDriverSeatEmployee(this.dragging); this.dragging = null; }
                        this.overDriver = false;
                    }
                }"
            @else
                x-data="{}"
            @endif
        >

            <div
                @if($interactive) wire:key="{{ $wireKeyPrefix }}-driver" @endif
                class="lvs-seat lvs-seat-driver"
                @if($interactive)
                    :class="{ 'lvs-seat-drag-over': overDriver }"
                    x-on:dragover.prevent="overDriver = true"
                    x-on:dragleave="overDriver = false"
                    x-on:drop.prevent="dropOnDriver()"
                @endif
            >

                <span class="lvs-label" style="color:#a5b4fc; opacity:.8;">
                    <i class="bi bi-steering-wheel me-1"></i>Kierowca
                </span>

                @if(!$isExternalDriver && $driverEmployeeId && $driverEmployee)
                    @if($driverEmployee->image_url ?? null)
                        <img src="{{ $driverEmployee->image_url }}" class="lvs-avatar" alt="">
                    @else
                        <div class="lvs-avatar-placeholder" style="background:rgba(99,102,241,0.35); color:#a5b4fc;">
                            {{ mb_strtoupper(mb_substr($driverEmployee->first_name, 0, 1).mb_substr($driverEmployee->last_name, 0, 1)) }}
                        </div>
                    @endif
                    <span class="small fw-semibold text-center text-truncate w-100" style="font-size:.75rem; max-width:120px;">
                        {{ $driverEmployee->full_name }}
                    </span>
                    @if($interactive)
                        <button type="button"
                                class="btn btn-link text-danger p-0 position-absolute"
                                style="top:6px; right:6px; font-size:.75rem; line-height:1;"
                                title="Usuń z roli kierowcy"
                                wire:click="assignDriverSeatEmployee(null)">
                            <i class="bi bi-x-circle-fill"></i>
                        </button>
                    @endif
                @elseif($isExternalDriver)
                    <i class="bi bi-person-fill lvs-seat-icon" style="opacity:.3; font-size:1.8rem;"></i>
                    <span class="small text-center" style="font-size:.72rem; color:#94a3b8; line-height:1.3;">Zewnętrzny</span>
                @else
                    <i class="bi bi-exclamation-triangle" style="color:#f59e0b; font-size:1.1rem; opacity:.8;"></i>
                    <span class="small text-center" style="font-size:.68rem; color:#f59e0b; line-height:1.2; margin-bottom:2px;">Wybierz kierowcę</span>
                    @if($interactive)
                        <select class="form-select form-select-sm w-100"
                                style="font-size:.7rem; padding: 3px 24px 3px 6px; height:28px; min-height:28px; border-radius:8px;"
                                x-on:change="$wire.assignDriverSeatEmployee($event.target.value ? parseInt($event.target.value) : null)">
                            <option value="">— pasażer —</option>
                            @foreach($driverCandidates as $dc)
                                <option value="{{ $dc->id }}">{{ $dc->full_name }}</option>
                            @endforeach
                        </select>
                    @else
                        <select class="form-select form-select-sm w-100" disabled
                                style="font-size:.7rem; padding: 3px 24px 3px 6px; height:28px; min-height:28px; border-radius:8px;">
                            <option>— demo —</option>
                        </select>
                    @endif
                @endif

                <label class="d-flex align-items-center gap-1 mt-auto" style="cursor:{{ $interactive ? 'pointer' : 'default' }}; font-size:.7rem; white-space:nowrap;">
                    <input type="checkbox" class="form-check-input mt-0" style="width:11px;height:11px;"
                           {{ $isExternalDriver ? 'checked' : '' }}
                           @if($interactive)
                               wire:change="toggleExternalDriver()"
                           @else
                               disabled
                           @endif
                    >
                    <span style="color:#94a3b8;">Zewnętrzny</span>
                </label>
            </div>

            @foreach($passengerSlots as $slotIdx => $emp)
                @if($emp)
                    <div @if($interactive) wire:key="{{ $wireKeyPrefix }}-seat-{{ $slotIdx }}-{{ $emp->id }}" @endif
                         class="lvs-seat lvs-seat-occupied"
                         @if($interactive)
                             draggable="true"
                             x-on:dragstart="startDrag({{ $emp->id }})"
                         @endif
                         title="{{ $interactive ? 'Przeciągnij na fotel kierowcy' : '' }}">
                        <span class="lvs-label">Pasażer {{ $slotIdx }}</span>
                        @if($emp->image_url ?? null)
                            <img src="{{ $emp->image_url }}" class="lvs-avatar" alt="">
                        @else
                            <div class="lvs-avatar-placeholder" style="background:rgba(148,163,184,0.2); color:#94a3b8;">
                                {{ mb_strtoupper(mb_substr($emp->first_name, 0, 1).mb_substr($emp->last_name, 0, 1)) }}
                            </div>
                        @endif
                        <span class="small text-center text-truncate w-100" style="font-size:.75rem; max-width:110px;">
                            {{ $emp->full_name }}
                        </span>
                        @if($interactive)
                            <i class="bi bi-grip-horizontal position-absolute text-muted" style="bottom:6px; font-size:.65rem; opacity:.4;"></i>
                        @endif
                    </div>
                @else
                    <div @if($interactive) wire:key="{{ $wireKeyPrefix }}-seat-{{ $slotIdx }}-empty" @endif
                         class="lvs-seat lvs-seat-empty">
                        <span class="lvs-label">Pasażer {{ $slotIdx }}</span>
                        <svg width="32" height="38" viewBox="0 0 32 38" fill="none" xmlns="http://www.w3.org/2000/svg" opacity=".22">
                            <rect x="2" y="2" width="28" height="20" rx="6" stroke="currentColor" stroke-width="2"/>
                            <path d="M4 22 Q2 30 6 34 Q10 38 16 38 Q22 38 26 34 Q30 30 28 22" stroke="currentColor" stroke-width="2" fill="none"/>
                            <line x1="6" y1="22" x2="26" y2="22" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <span style="font-size:.72rem; color:#64748b;">Wolne</span>
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</div>
