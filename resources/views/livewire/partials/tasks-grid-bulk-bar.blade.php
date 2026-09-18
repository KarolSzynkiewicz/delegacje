@if(! $this->isPlanQueue() && $this->normalizedSelectedIds() !== [])
    @php
        $selectedCount = count($this->normalizedSelectedIds());
        $bulkFields = $this->bulkWritableFields();
        $bulkField = $this->bulkField;
        $canApply = $bulkField !== '' && array_key_exists($bulkField, $bulkFields)
            && ($bulkField !== 'status' || $this->bulkValue !== '');
    @endphp
    <div class="tg-bulk-bar" wire:key="tg-bulk-{{ $selectedCount }}-{{ $bulkField }}">
        <span class="tg-bulk-bar__count font-mono">
            Wybrano {{ $selectedCount }}
        </span>
        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="toggleSelectVisible">
            {{ $this->pageIsFullySelected() ? 'Odznacz widoczne' : 'Zaznacz widoczne' }}
        </button>
        <div class="tg-bulk-bar__mutate">
            <select class="form-select form-select-sm" wire:model.live="bulkField" aria-label="Co zmieniasz">
                <option value="">Co zmieniasz…</option>
                @foreach($bulkFields as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>

            @if($bulkField === 'assigned_to')
                <select class="form-select form-select-sm" wire:model.live="bulkValue" aria-label="Na co">
                    <option value="">Nieprzypisane</option>
                    @foreach($allUsers as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            @elseif($bulkField === 'status')
                <select class="form-select form-select-sm" wire:model.live="bulkValue" aria-label="Na co">
                    <option value="">Wybierz…</option>
                    @foreach(\App\Enums\TaskStatus::cases() as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>
            @elseif($bulkField === 'sprint')
                <select class="form-select form-select-sm" wire:model.live="bulkValue" aria-label="Na co">
                    <option value="">Poza sprintem</option>
                    @foreach($allSprints as $sprintOption)
                        <option value="{{ $sprintOption->id }}">{{ $sprintOption->label() }}</option>
                    @endforeach
                </select>
            @elseif($bulkField === 'priority')
                <select class="form-select form-select-sm" wire:model.live="bulkValue" aria-label="Na co">
                    <option value="">Brak</option>
                    <option value="1">Najniższy</option>
                    <option value="2">Niski</option>
                    <option value="3">Średni</option>
                    <option value="4">Wysoki</option>
                    <option value="5">Krytyczny</option>
                </select>
            @elseif($bulkField === 'due_date')
                <input type="date" class="form-control form-control-sm" wire:model.live="bulkValue" aria-label="Na co">
            @elseif($bulkField === 'category')
                <input type="text"
                       class="form-control form-control-sm"
                       wire:model.live="bulkValue"
                       placeholder="Kategoria"
                       maxlength="255"
                       aria-label="Na co">
            @else
                <input type="text" class="form-control form-control-sm" disabled placeholder="Najpierw pole" aria-label="Na co">
            @endif

            <button type="button"
                    class="btn btn-sm btn-primary"
                    wire:click="bulkApply"
                    @disabled(! $canApply)>
                Zastosuj
            </button>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" wire:click="clearSelection" title="Odznacz wszystko">
            ×
        </button>
    </div>
@endif
