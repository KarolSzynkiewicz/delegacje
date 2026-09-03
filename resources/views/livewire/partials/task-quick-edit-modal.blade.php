@if($quickEditTaskId)
    {{-- Teleport poza .card: backdrop-filter na karcie więzi position:fixed
         i okno ląduje pod kolejnymi sekcjami zamiast na viewportcie. --}}
    @teleport('body')
    <div
        class="task-qe-overlay"
        wire:click="closeQuickEdit"
        wire:key="qe-{{ $quickEditTaskId }}-{{ $quickEditField }}-{{ (int) ($quickEditClientX ?? -1) }}-{{ (int) ($quickEditClientY ?? -1) }}"
        x-data="{
            placePanel(el) {
                if (!el) return;
                const x = @js($quickEditClientX);
                const y = @js($quickEditClientY);
                el.style.position = 'fixed';
                el.style.zIndex = '100001';
                el.style.maxWidth = 'min(420px, calc(100vw - 24px))';
                if (x === null || y === null) {
                    el.style.left = '50%';
                    el.style.top = '50%';
                    el.style.transform = 'translate(-50%, -50%)';
                    return;
                }
                let left = x;
                let top = y + 8;
                el.style.left = left + 'px';
                el.style.top = top + 'px';
                el.style.transform = 'translate(-50%, 0)';
                this.$nextTick(() => {
                    const r = el.getBoundingClientRect();
                    let sx = 0;
                    let sy = 0;
                    if (r.left < 8) sx = 8 - r.left;
                    if (r.right > window.innerWidth - 8) sx = window.innerWidth - 8 - r.right;
                    if (r.top < 8) sy = 8 - r.top;
                    if (r.bottom > window.innerHeight - 8) sy = window.innerHeight - 8 - r.bottom;
                    if (sx !== 0 || sy !== 0) {
                        el.style.left = (left + sx) + 'px';
                        el.style.top = (top + sy) + 'px';
                    }
                });
            },
            focusField(el) {
                this.$nextTick(() => {
                    const panel = el?.querySelector?.('[data-quick-focus]');
                    panel?.focus?.();
                });
            }
        }"
        x-init="$nextTick(() => { placePanel($refs.qePanel); focusField($refs.qePanel); })"
        x-on:keydown.escape.window="$wire.closeQuickEdit()"
        role="dialog"
        aria-modal="true"
    >
        <div
            class="task-qe-panel"
            wire:click.stop
            x-ref="qePanel"
        >
            <h6 class="mb-3 fw-semibold">
                @if($quickEditField === 'category')
                    Kategoria
                @elseif($quickEditField === 'assigned_to')
                    Przypisany
                @elseif($quickEditField === 'sprint_id')
                    Sprint
                @elseif($quickEditField === 'priority')
                    Priorytet
                @else
                    Termin wykonania
                @endif
            </h6>
            @if($quickEditField === 'category')
                <div class="mb-3">
                    <label class="form-label small text-muted mb-1">Nazwa kategorii</label>
                    <input type="text" class="form-control form-control-sm" wire:model="qeCategory" placeholder="Opcjonalnie" maxlength="255" data-quick-focus>
                    @error('qeCategory') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            @elseif($quickEditField === 'assigned_to')
                <div class="mb-3">
                    <label class="form-label small text-muted mb-1">Wybierz użytkownika</label>
                    <select class="form-select form-select-sm" wire:model="qeAssignedTo" data-quick-focus>
                        <option value="">Brak przypisania</option>
                        @foreach($allUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                    @error('qeAssignedTo') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            @elseif($quickEditField === 'sprint_id')
                <div class="mb-3">
                    <label class="form-label small text-muted mb-1">Wybierz sprint</label>
                    <select class="form-select form-select-sm" wire:model="qeSprintId" data-quick-focus>
                        <option value="">Poza sprintem</option>
                        @foreach(($sprints ?? []) as $sprint)
                            <option value="{{ $sprint->id }}">{{ $sprint->label() }}</option>
                        @endforeach
                    </select>
                    @error('qeSprintId') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            @elseif($quickEditField === 'priority')
                <div class="mb-3">
                    <label class="form-label small text-muted mb-1">Priorytet</label>
                    <select class="form-select form-select-sm" wire:model="qePriority" data-quick-focus>
                        <option value="">Brak</option>
                        <option value="1">1 – Najniższy</option>
                        <option value="2">2 – Niski</option>
                        <option value="3">3 – Średni</option>
                        <option value="4">4 – Wysoki</option>
                        <option value="5">5 – Krytyczny</option>
                    </select>
                    @error('qePriority') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            @else
                <div class="mb-3">
                    <label class="form-label small text-muted mb-1">Data</label>
                    <input type="date" class="form-control form-control-sm" wire:model="qeDueDate" data-quick-focus>
                    @error('qeDueDate') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            @endif
            <div class="d-flex gap-2 justify-content-end">
                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="closeQuickEdit">Anuluj</button>
                <button type="button" class="btn btn-sm btn-primary" wire:click="saveQuickEdit">Zapisz</button>
            </div>
        </div>
    </div>
    @endteleport
@endif
