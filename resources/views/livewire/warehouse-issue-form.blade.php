<div>
    <x-ui.errors />

    @error('lines')
        <div class="text-danger small mb-3">{{ $message }}</div>
    @enderror

    @if($confirming && $preview)
        <x-ui.card label="Podsumowanie zlecenia wydania">
            <p class="text-muted small mb-4">
                Planista rezerwuje sztuki na półce — nie schodzą jeszcze ze stanu magazynu. Magazynier wydaje sprzęt z dokumentu ZW.
            </p>
            @include('equipment-issues._dispatch-summary', ['summary' => $preview])
            <div class="row g-3 mt-1 mb-3">
                <div class="col-md-6">
                    <x-ui.input
                        type="select"
                        name="assigneeId"
                        wire:model="assigneeId"
                        label="Przypisz kompletację"
                        required
                    >
                        <option value="">Wybierz osobę…</option>
                        @foreach($assignees as $assignee)
                            <option value="{{ $assignee->id }}">{{ $assignee->name }}</option>
                        @endforeach
                    </x-ui.input>
                    <p class="small text-muted mb-0 mt-1">
                        Powstanie zadanie z linkiem do dokumentu ZW.
                    </p>
                    @error('assigneeId')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="d-flex justify-content-end align-items-center gap-2 mt-2">
                <x-ui.button variant="ghost" type="button" wire:click="backToEdit" action="back">
                    Wróć do koszyka
                </x-ui.button>
                <x-ui.button variant="primary" type="button" wire:click="confirm" wire:loading.attr="disabled" action="save">
                    Zatwierdź zlecenie
                </x-ui.button>
            </div>
        </x-ui.card>
    @else
    <livewire:employee-picker
        :selected-employee-ids="$employeeIds"
        :exclude-terminated="true"
        :show-card="false"
        :required="true"
        label="Wydanie dla"
        notify-event="warehouse-issue-employees-updated"
        :key="'issue-emp-picker-'.$warehouseId"
    />
    @error('employeeIds')
        <div class="text-danger small mb-3">{{ $message }}</div>
    @enderror

    <div
        x-data="{
            payload: null,
            start(event, type, id) {
                this.payload = { type, id };
                event.dataTransfer.setData('text/plain', JSON.stringify(this.payload));
                event.dataTransfer.effectAllowed = 'move';
            },
            over(event, zone) {
                const p = this.payload;
                if (! p) return;
                const ok = (zone === 'stock' && p.type === 'cart') || (p.type === 'stock' && zone === 'cart');
                if (ok) {
                    event.preventDefault();
                    event.dataTransfer.dropEffect = 'move';
                }
            },
            drop(event, zone) {
                event.preventDefault();
                let p = this.payload;
                try {
                    p = JSON.parse(event.dataTransfer.getData('text/plain') || '{}');
                } catch (e) {}
                if (! p || ! p.id) return;
                if (zone === 'stock' && p.type === 'cart') {
                    $wire.removeTypeFromCart(Number(p.id));
                } else if (p.type === 'stock' && zone === 'cart') {
                    $wire.addToCart(Number(p.id));
                }
                this.payload = null;
            }
        }"
    >

    <style>
        .warehouse-issue-board {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .warehouse-issue-board__lanes {
            min-height: 22rem;
        }
        @media (min-width: 992px) {
            .warehouse-issue-board {
                display: grid;
                grid-template-columns: minmax(0, 5fr) minmax(0, 7fr);
                grid-template-rows: auto minmax(22rem, 1fr) auto;
                column-gap: 0;
                row-gap: .75rem;
                align-items: stretch;
            }
            .warehouse-issue-board__left-head { grid-column: 1; grid-row: 1; padding-right: 1.5rem; }
            .warehouse-issue-board__left-lanes {
                grid-column: 1;
                grid-row: 2;
                padding-right: 1.5rem;
                display: flex;
                flex-direction: column;
            }
            .warehouse-issue-board__right-head {
                grid-column: 2;
                grid-row: 1;
                padding-left: 2rem;
                border-left: 2px solid var(--glass-border);
            }
            .warehouse-issue-board__right-lanes {
                grid-column: 2;
                grid-row: 2;
                padding-left: 2rem;
                border-left: 2px solid var(--glass-border);
                display: flex;
                flex-direction: column;
            }
            .warehouse-issue-board__notes {
                grid-column: 2;
                grid-row: 3;
                padding-left: 2rem;
                border-left: 2px solid var(--glass-border);
            }
            .warehouse-issue-board__lanes { height: 100%; min-height: 22rem; }
        }
        .warehouse-issue-item {
            background: var(--bg-input);
            border: 1px solid var(--glass-border);
            color: var(--text-main);
            border-radius: 12px;
        }
        .warehouse-issue-item.is-muted {
            opacity: .45;
        }
        .warehouse-issue-tip {
            position: absolute;
            left: 0;
            top: calc(100% + 4px);
            z-index: 8;
            min-width: 9rem;
            padding: .4rem .6rem;
            border-radius: 8px;
            background: #0f172a;
            color: var(--text-main);
            border: 1px solid var(--glass-border);
            white-space: nowrap;
            font-weight: 500;
            font-size: .72rem;
            line-height: 1.35;
        }
        .warehouse-issue-size-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 .5rem;
        }
        .warehouse-issue-size-table th {
            color: var(--text-muted);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding: 0 1rem .5rem;
        }
        .warehouse-issue-size-table td {
            padding: .75rem 1rem;
            vertical-align: middle;
            background: var(--bg-input);
            border-top: 1px solid var(--glass-border);
            border-bottom: 1px solid var(--glass-border);
            color: var(--text-main);
        }
        .warehouse-issue-size-table td:first-child {
            border-left: 1px solid var(--glass-border);
            border-radius: 12px 0 0 12px;
        }
        .warehouse-issue-size-table td:last-child {
            border-right: 1px solid var(--glass-border);
            border-radius: 0 12px 12px 0;
        }
        .warehouse-issue-last-hint {
            background: transparent;
            border: 1px dashed var(--glass-border);
            color: var(--text-muted);
            border-radius: 8px;
            padding: .35rem .65rem;
            font-size: .8rem;
        }
        .warehouse-issue-last-hint:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        .warehouse-issue-returnable {
            position: relative;
            display: inline-flex;
            align-items: center;
            color: var(--text-muted);
            font-size: .85rem;
            line-height: 1;
            cursor: help;
        }
        .warehouse-issue-returnable:hover {
            color: var(--primary);
        }
        .warehouse-issue-thumb {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid var(--glass-border);
            flex-shrink: 0;
            background: rgba(255, 255, 255, .04);
        }
        .warehouse-issue-thumb.is-placeholder {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-size: .85rem;
        }
        .warehouse-issue-legend-badge {
            background: transparent !important;
            border: 1px dashed var(--glass-border);
            color: var(--text-muted) !important;
            font-weight: 600;
        }
        .warehouse-issue-size-badge.is-over {
            background: rgba(239, 68, 68, .18) !important;
            color: #fecaca !important;
        }
        .warehouse-issue-size-overlay {
            position: fixed !important;
            inset: 0 !important;
            z-index: 99999 !important;
            display: flex !important;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: rgba(0, 0, 0, 0.75);
        }
        .warehouse-issue-size-dialog {
            width: min(960px, 100%);
            max-height: min(90vh, 920px);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: #1e293b;
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            box-shadow: 0 24px 64px rgba(0, 0, 0, 0.55);
            color: var(--text-main);
        }
        .warehouse-issue-size-dialog__header,
        .warehouse-issue-size-dialog__footer {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.1rem 1.25rem;
            border-bottom: 1px solid var(--glass-border);
            flex-shrink: 0;
        }
        .warehouse-issue-size-dialog__footer {
            align-items: center;
            justify-content: flex-end;
            border-bottom: 0;
            border-top: 1px solid var(--glass-border);
        }
        .warehouse-issue-size-dialog__body {
            overflow-y: auto;
            padding: 1.25rem;
            min-height: 0;
        }
    </style>

    <div class="warehouse-issue-board">
        <div class="warehouse-issue-board__left-head">
            <label class="form-label" for="issue-warehouse">Magazyn</label>
            <select id="issue-warehouse" class="form-select" wire:model.live="warehouseId">
                @foreach($warehouses as $option)
                    <option value="{{ $option->id }}">
                        {{ $option->display_name }}{{ $option->is_default ? ' — siedziba' : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div
            class="warehouse-issue-board__left-lanes"
            x-on:dragover="over($event, 'stock')"
            x-on:drop="drop($event, 'stock')"
        >
            <div class="warehouse-issue-board__lanes h-100">
                @include('livewire.partials.warehouse-issue-lane', [
                    'title' => 'Asortyment',
                    'cards' => $stockCards,
                    'empty' => 'Brak sprzętu do wydania.',
                    'side' => 'stock',
                    'multipleRecipients' => $multipleRecipients,
                ])
            </div>
        </div>

        <div class="warehouse-issue-board__right-head">
            <label class="form-label" for="issue-date">Data wydania <span class="text-danger">*</span></label>
            <input id="issue-date" type="date" class="form-control @error('issueDate') is-invalid @enderror" wire:model="issueDate">
            @error('issueDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="warehouse-issue-board__right-lanes">
            <div class="warehouse-issue-board__lanes h-100">
                @include('livewire.partials.warehouse-issue-lane', [
                    'title' => 'Do wydania',
                    'cards' => $cartCards,
                    'empty' => 'Upuść pozycje tutaj.',
                    'side' => 'cart',
                    'multipleRecipients' => $multipleRecipients,
                ])
            </div>
        </div>

        <div class="warehouse-issue-board__notes">
            <label class="form-label" for="issue-notes">Notatki</label>
            <textarea id="issue-notes" class="form-control @error('notes') is-invalid @enderror" rows="2" wire:model="notes"></textarea>
        </div>
    </div>

    <div class="d-flex justify-content-end align-items-center gap-2 mt-4">
        <x-ui.button variant="ghost" href="{{ route('equipment.tab.issues') }}" action="cancel">
            Anuluj
        </x-ui.button>
        <x-ui.button variant="primary" type="button" wire:click="prepare" wire:loading.attr="disabled">
            Dalej: podsumowanie
        </x-ui.button>
    </div>
    @endif

    {{-- Overlay bez teleportu: @teleport + .card backdrop-filter chowały panel. --}}
    @if($sizePanel)
        <div
            class="warehouse-issue-size-overlay"
            wire:key="warehouse-issue-size-{{ $sizePanel['type']->id }}"
            wire:click.self="closeSizePanel"
            role="dialog"
            aria-modal="true"
            aria-labelledby="warehouse-issue-size-title"
            style="position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;padding:1.5rem;background:rgba(0,0,0,.75);"
        >
                <div class="warehouse-issue-size-dialog" style="width:min(960px,100%);max-height:min(90vh,920px);display:flex;flex-direction:column;overflow:hidden;background:#1e293b;border:1px solid var(--glass-border);border-radius:16px;box-shadow:0 24px 64px rgba(0,0,0,.55);">
                    <div class="warehouse-issue-size-dialog__header">
                        <div>
                            <h5 id="warehouse-issue-size-title" class="mb-1">{{ $sizePanel['type']->name }} — rozmiar per osoba</h5>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                @foreach($sizePanel['stock'] as $option)
                                    <x-ui.badge
                                        variant="{{ $option['over'] ? 'danger' : 'secondary' }}"
                                        @class(['warehouse-issue-size-badge', 'is-over' => $option['over']])
                                    >
                                        {{ $option['variant']->kind_label }} · {{ $option['stock'] }}
                                        @if($option['requested'] > 0)
                                            · wybrane {{ $option['requested'] }}
                                        @endif
                                    </x-ui.badge>
                                @endforeach
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeSizePanel" aria-label="Zamknij"></button>
                    </div>
                    <div class="warehouse-issue-size-dialog__body">
                        @if($sizePanel['missing'] > 0 || count($sizePanel['shortages']) > 0 || $errors->has('sizePanel'))
                            <div class="alert alert-danger py-2 px-3 small mb-3">
                                @if($sizePanel['missing'] > 0)
                                    <div>Uzupełnij rozmiar dla każdej osoby.</div>
                                @endif
                                @foreach($sizePanel['shortages'] as $shortage)
                                    <div>Rozmiar {{ $shortage['label'] }}: wybrane {{ $shortage['requested'] }}, dostępne {{ $shortage['stock'] }}.</div>
                                @endforeach
                                @foreach($errors->get('sizePanel') as $message)
                                    @if(
                                        $message !== 'Uzupełnij rozmiar dla każdej osoby.'
                                        && ! str_starts_with($message, 'Rozmiar ')
                                    )
                                        <div>{{ $message }}</div>
                                    @endif
                                @endforeach
                            </div>
                        @endif

                        <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
                            <span class="small" style="color:var(--text-muted);">Wszyscy</span>
                            <select
                                class="form-select"
                                style="max-width:16rem;"
                                wire:change="applyVariantToAll({{ $sizePanel['type']->id }}, $event.target.value)"
                            >
                                <option value="">wybierz rozmiar</option>
                                @foreach($sizePanel['variants'] as $variant)
                                    <option value="{{ $variant->id }}">{{ $variant->kind_label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <table class="warehouse-issue-size-table">
                            <thead>
                                <tr>
                                    <th style="width:40%;">Pracownik</th>
                                    <th style="width:25%;">Rozmiar</th>
                                    <th style="width:12%;">Ilość</th>
                                    <th style="width:23%;">Podpowiedź</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sizePanel['assignments'] as $row)
                                    <tr wire:key="size-row-{{ $sizePanel['type']->id }}-{{ $row['employee']->id }}">
                                        <td class="fw-semibold">{{ $row['employee']->full_name }}</td>
                                        <td>
                                            <select
                                                class="form-select"
                                                wire:change="setAssignmentVariant({{ $sizePanel['type']->id }}, {{ $row['employee']->id }}, $event.target.value)"
                                            >
                                                <option value="">— wybierz</option>
                                                @foreach($sizePanel['variants'] as $variant)
                                                    <option value="{{ $variant->id }}" @selected($row['variant_id'] === $variant->id)>
                                                        {{ $variant->kind_label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input
                                                type="number"
                                                min="1"
                                                class="form-control"
                                                style="max-width:6.5rem;"
                                                value="{{ $row['quantity'] }}"
                                                wire:change="setAssignmentQuantity({{ $sizePanel['type']->id }}, {{ $row['employee']->id }}, $event.target.value)"
                                            >
                                        </td>
                                        <td>
                                            @if($row['last_variant_id'] && $row['variant_id'] === null)
                                                <button
                                                    type="button"
                                                    class="warehouse-issue-last-hint"
                                                    wire:click="setAssignmentVariant({{ $sizePanel['type']->id }}, {{ $row['employee']->id }}, {{ $row['last_variant_id'] }})"
                                                    title="Ustaw ostatni rozmiar"
                                                >
                                                    {{ $row['last_label'] }}
                                                </button>
                                            @elseif($row['last_label'])
                                                <span class="small" style="color:var(--text-muted);">{{ $row['last_label'] }}</span>
                                            @else
                                                <span class="small" style="color:var(--text-muted);">brak historii</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="warehouse-issue-size-dialog__footer">
                        <x-ui.button variant="ghost" type="button" wire:click="closeSizePanel">
                            Zamknij
                        </x-ui.button>
                        <x-ui.button variant="primary" type="button" wire:click="confirmSizePanel">
                            Gotowe
                        </x-ui.button>
                    </div>
                </div>
            </div>
    @endif
</div>
