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

<x-ui.card @class(['dt-card', 'cr-card', 'cr-card--irrelevant' => $irrelevant]) wire:key="cost-entry-card-{{ $id }}">
    <div class="dt-card__title mb-2 d-flex justify-content-between align-items-start gap-2">
        <div class="flex-grow-1 min-w-0">
            <input
                type="text"
                class="form-control form-control-sm cr-inline cr-inline--name w-100 @error("rows.$id.name") is-invalid @enderror"
                wire:model.blur="rows.{{ $id }}.name"
                value="{{ $rows[$id]['name'] ?? $entry->name }}"
                placeholder="Nazwa kosztu"
                maxlength="255"
                aria-label="Nazwa kosztu"
                @disabled(! $canUpdate)
            >
            @error("rows.$id.name")<span class="cr-inline-error">{{ $message }}</span>@enderror
            @if($context)
                <div class="cr-context ps-2 mt-1">{{ $context }}</div>
            @endif
        </div>
        <x-ui.badge variant="{{ $entry->sourceBadgeVariant() }}">{{ $entry->source_type_label }}</x-ui.badge>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Kwota</span>
        <span class="dt-card__value">
            @if($amount !== null)
                <span class="cr-amount fw-semibold font-mono">{{ number_format($amount, 2, ',', ' ') }} {{ $currency }}</span>
            @else
                —
            @endif
            <span class="cr-date font-mono ms-auto">{{ $sourceDate?->format('Y-m-d') ?? '' }}</span>
        </span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Spółka</span>
        <span class="dt-card__value">
            <select
                class="form-select form-select-sm cr-inline w-100 @error("rows.$id.company_id") is-invalid @enderror"
                wire:model.live="rows.{{ $id }}.company_id"
                aria-label="Spółka"
                @disabled(! $canUpdate)
            >
                <option value="">— bez spółki —</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>
        </span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Miesiąc rozl.</span>
        <span class="dt-card__value">
            <select
                class="form-select form-select-sm cr-inline w-100 @error("rows.$id.period") is-invalid @enderror"
                wire:model.live="rows.{{ $id }}.period"
                aria-label="Miesiąc rozliczeniowy"
                @disabled(! $canUpdate)
            >
                @foreach($periodOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Istotny</span>
        <span class="dt-card__value">
            <button
                type="button"
                class="cr-toggle {{ $irrelevant ? '' : 'cr-toggle--on' }}"
                wire:click="toggleIrrelevant({{ $id }})"
                wire:loading.attr="disabled"
                wire:target="toggleIrrelevant({{ $id }})"
                role="switch"
                aria-checked="{{ $irrelevant ? 'false' : 'true' }}"
                aria-label="Istotny finansowo"
                @disabled(! $canUpdate)
            ></button>
            <span class="small text-muted">{{ $irrelevant ? 'nieistotny — poza sumą' : 'wliczany do sumy' }}</span>
        </span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Komentarz</span>
        <span class="dt-card__value">
            <input
                type="text"
                class="form-control form-control-sm cr-inline cr-inline--comment w-100 @error("rows.$id.comment") is-invalid @enderror"
                wire:model.blur="rows.{{ $id }}.comment"
                value="{{ $rows[$id]['comment'] ?? ($entry->comment ?? '') }}"
                placeholder="Komentarz…"
                aria-label="Komentarz"
                @disabled(! $canUpdate)
            >
            @error("rows.$id.comment")<span class="cr-inline-error">{{ $message }}</span>@enderror
        </span>
    </div>

    @if($sourceUrl)
        <div class="dt-card__actions">
            <a href="{{ $sourceUrl }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-box-arrow-up-right me-1"></i>Źródło
            </a>
        </div>
    @endif
</x-ui.card>
