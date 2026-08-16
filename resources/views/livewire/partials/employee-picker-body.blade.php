@if($employees->total() === 0 && trim($employeeSearch) === '')
    <p class="small text-muted mb-0">Brak pracowników w systemie.</p>
@else
    <div class="mb-3">
        @if($showCard)
            <label class="form-label small text-muted mb-1" for="{{ $filterId }}">Szukaj pracownika</label>
        @endif
        <input
            id="{{ $filterId }}"
            type="search"
            class="form-control form-control-sm logistics-trip-header-control"
            placeholder="Imię lub nazwisko…"
            autocomplete="off"
            wire:model.live.debounce.300ms="employeeSearch"
        >
    </div>

    @if($selectedEmployees->isNotEmpty())
        <div class="d-flex flex-wrap gap-1 mb-3">
            @foreach($selectedEmployees as $emp)
                <button
                    type="button"
                    class="btn btn-sm d-inline-flex align-items-center gap-1"
                    style="background:rgba(59,130,246,0.15);border:1px solid rgba(59,130,246,0.4);color:var(--text-main);"
                    wire:click="toggleEmployee({{ $emp->id }})"
                    wire:key="employee-picker-selected-{{ $emp->id }}"
                    title="Usuń"
                >
                    {{ $emp->full_name }}
                    <i class="bi bi-x-lg" style="font-size:.7rem;"></i>
                </button>
            @endforeach
        </div>
    @endif

    @if($employees->isEmpty())
        <p class="small text-muted mb-0">Brak wyników dla „{{ $employeeSearch }}”.</p>
    @else
        <div class="row g-2" wire:key="employee-picker-grid-{{ $employees->currentPage() }}-{{ $employeeSearch }}">
            @foreach($employees as $emp)
                @php $checked = in_array((int) $emp->id, $selectedIds, true); @endphp
                <div class="col-sm-6 col-md-4 col-lg-3" wire:key="employee-picker-{{ $emp->id }}">
                    <div class="rounded-2 px-3 py-2 {{ $checked ? 'border border-primary border-opacity-50' : 'border border-secondary border-opacity-25' }}"
                         style="cursor:pointer; background:{{ $checked ? 'rgba(59,130,246,0.08)' : 'transparent' }};"
                         wire:click="toggleEmployee({{ $emp->id }})">
                        <div class="form-check mb-0">
                            <input class="form-check-input"
                                   type="checkbox"
                                   @checked($checked)
                                   style="pointer-events:none;">
                            <label class="form-check-label small fw-semibold"
                                   style="pointer-events:none; cursor:pointer;">
                                {{ $emp->full_name }}
                            </label>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 pt-2" style="border-top: 1px solid rgba(255,255,255,0.08);">
            <span class="small text-muted">
                @if($selectedIds !== [])
                    Wybrano: <strong>{{ count($selectedIds) }}</strong>
                    {{ count($selectedIds) === 1 ? 'osoba' : 'osób' }}
                    <span class="text-muted">·</span>
                @endif
                {{ $employees->total() }} {{ $employees->total() === 1 ? 'osoba' : 'osób' }}
            </span>
            {{ $employees->onEachSide(1)->links() }}
        </div>
    @endif
@endif
