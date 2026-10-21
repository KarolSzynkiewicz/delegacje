@props(['entry', 'canUpdate' => false])

@php
    $id = $entry->id;
    $amount = $entry->amount();
    $currency = $entry->currency();
    $sourceDate = $entry->sourceDate();
    $context = $entry->sourceContext();
    $sourceUrl = $entry->sourceUrl();
    $irrelevant = $entry->financially_irrelevant;
@endphp

<tr
    wire:key="cost-entry-{{ $id }}"
    @class(['cr-row', 'cr-row--irrelevant' => $irrelevant])
    :class="{ 'cr-row--saved': savedId === {{ $id }} }"
>
    <td class="cr-dim">
        <input
            type="text"
            class="form-control form-control-sm cr-inline cr-inline--name @error("rows.$id.name") is-invalid @enderror"
            wire:model.blur="rows.{{ $id }}.name"
            value="{{ $rows[$id]['name'] ?? $entry->name }}"
            placeholder="Nazwa kosztu"
            maxlength="255"
            aria-label="Nazwa kosztu"
            @disabled(! $canUpdate)
        >
        @error("rows.$id.name")<span class="cr-inline-error">{{ $message }}</span>@enderror
        @if($context)
            <div class="cr-context ps-2" title="{{ $context }}">{{ $context }}</div>
        @endif
    </td>

    <td class="cr-dim">
        <x-ui.badge variant="{{ $entry->sourceBadgeVariant() }}">{{ $entry->source_type_label }}</x-ui.badge>
    </td>

    <td class="cr-dim">
        <select
            class="form-select form-select-sm cr-inline cr-inline--company @error("rows.$id.company_id") is-invalid @enderror"
            wire:model.live="rows.{{ $id }}.company_id"
            aria-label="Spółka"
            @disabled(! $canUpdate)
        >
            <option value="">— bez spółki —</option>
            @foreach($companies as $company)
                <option value="{{ $company->id }}">{{ $company->name }}</option>
            @endforeach
        </select>
        @error("rows.$id.company_id")<span class="cr-inline-error">{{ $message }}</span>@enderror
    </td>

    <td class="cr-dim">
        <select
            class="form-select form-select-sm cr-inline cr-inline--period @error("rows.$id.period") is-invalid @enderror"
            wire:model.live="rows.{{ $id }}.period"
            aria-label="Miesiąc rozliczeniowy"
            @disabled(! $canUpdate)
        >
            @foreach($periodOptions as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        @error("rows.$id.period")<span class="cr-inline-error">{{ $message }}</span>@enderror
    </td>

    <td class="cr-dim">
        <span class="cr-date font-mono">{{ $sourceDate?->format('Y-m-d') ?? '—' }}</span>
    </td>

    <td class="text-end text-nowrap cr-dim">
        @if($amount !== null)
            <span class="cr-amount fw-semibold font-mono">{{ number_format($amount, 2, ',', ' ') }}</span>
            <span class="cr-amount text-muted small">{{ $currency }}</span>
        @else
            <span class="text-muted">—</span>
        @endif
    </td>

    <td class="text-center">
        <button
            type="button"
            class="cr-toggle {{ $irrelevant ? '' : 'cr-toggle--on' }}"
            wire:click="toggleIrrelevant({{ $id }})"
            wire:loading.attr="disabled"
            wire:target="toggleIrrelevant({{ $id }})"
            role="switch"
            aria-checked="{{ $irrelevant ? 'false' : 'true' }}"
            aria-label="Istotny finansowo"
            title="{{ $irrelevant ? 'Nieistotny finansowo — kliknij, aby wliczać do sumy' : 'Istotny finansowo — kliknij, aby wyłączyć z sumy' }}"
            @disabled(! $canUpdate)
        ></button>
    </td>

    <td class="cr-dim">
        <input
            type="text"
            class="form-control form-control-sm cr-inline cr-inline--comment @error("rows.$id.comment") is-invalid @enderror"
            wire:model.blur="rows.{{ $id }}.comment"
            value="{{ $rows[$id]['comment'] ?? ($entry->comment ?? '') }}"
            placeholder="Komentarz…"
            aria-label="Komentarz"
            @disabled(! $canUpdate)
        >
        @error("rows.$id.comment")<span class="cr-inline-error">{{ $message }}</span>@enderror
    </td>

    <td class="text-end">
        @if($sourceUrl)
            <a href="{{ $sourceUrl }}" class="cr-source-link" title="Otwórz źródło: {{ $entry->source_type_label }}">
                <i class="bi bi-box-arrow-up-right"></i>
            </a>
        @endif
    </td>
</tr>
