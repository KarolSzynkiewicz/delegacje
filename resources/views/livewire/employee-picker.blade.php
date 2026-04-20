<div>
    @if(session()->has('warning'))
        <div class="alert alert-warning py-2 small mb-3">{{ session('warning') }}</div>
    @endif

    {{-- ── Uczestnicy ─────────────────────────────────────────────────────── --}}
    <x-ui.card label="Uczestnicy" class="mb-4">
        <div class="px-2 pb-2">
            @if($employees->total() === 0 && trim($employeeSearch) === '')
                <p class="small text-muted mb-0">Brak pracowników w systemie.</p>
            @else
                <div class="mb-3">
                    <label class="form-label small text-muted mb-1" for="employee-picker-filter">Szukaj pracownika</label>
                    <input
                        id="employee-picker-filter"
                        type="search"
                        class="form-control form-control-sm logistics-trip-header-control"
                        placeholder="Imię lub nazwisko…"
                        autocomplete="off"
                        wire:model.live.debounce.300ms="employeeSearch"
                    >
                </div>

                @if($employees->isEmpty())
                    <p class="small text-muted mb-0">Brak wyników dla „{{ $employeeSearch }}”.</p>
                @else
                    <div class="row g-2">
                        @foreach($employees as $emp)
                            @php $checked = in_array($emp->id, $selectedEmployeeIds, true); @endphp
                            <div class="col-sm-6 col-md-4 col-lg-3">
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
                            @if($selectedEmployeeIds !== [])
                                Wybrano: <strong class="text-white">{{ count($selectedEmployeeIds) }}</strong>
                                {{ count($selectedEmployeeIds) === 1 ? 'uczestnik' : 'uczestników' }}
                                <span class="text-muted">·</span>
                            @endif
                            Strona {{ $employees->currentPage() }} / {{ max(1, $employees->lastPage()) }}
                            ({{ $employees->total() }} {{ $employees->total() === 1 ? 'osoba' : 'osób' }})
                        </span>
                        <div class="pagination-sm mb-0">
                            {{ $employees->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </x-ui.card>
</div>
