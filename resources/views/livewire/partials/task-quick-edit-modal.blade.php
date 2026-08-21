@if($quickEditTaskId)
    <div
        class="position-fixed top-0 start-0 w-100 h-100"
        style="background: rgba(0, 0, 0, 0.4); z-index: 1050;"
        wire:click="closeQuickEdit"
        x-data="{
            placePanel(el) {
                if (!el) return;
                const x = @js($quickEditClientX);
                const y = @js($quickEditClientY);
                el.style.position = 'fixed';
                el.style.zIndex = '1051';
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
        wire:key="qe-{{ $quickEditTaskId }}-{{ $quickEditField }}-{{ (int) ($quickEditClientX ?? -1) }}-{{ (int) ($quickEditClientY ?? -1) }}"
        x-on:keydown.escape.window="$wire.closeQuickEdit()"
    >
        <div
            class="bg-white rounded shadow p-3"
            style="width: min(420px, calc(100vw - 24px));"
            wire:click.stop
            x-ref="qePanel"
        >
            <h6 class="mb-3 fw-semibold">
                @if($quickEditField === 'category')
                    Kategoria
                @elseif($quickEditField === 'assigned_to')
                    Przypisany
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
@endif
