@props([
    'document' => null,
])

@php
    $selected = old('planner_icon', $document?->planner_icon);
@endphp

<div class="mb-4">
    <label class="form-label">Ikona w plannerze</label>
    <p class="small text-muted mb-2">
        Wybierz ikonę, a planner pokaże ją przy pracownikach, którzy mają <strong>ważny</strong> ten dokument.
        Bez ikony dokument zostaje tylko w kartotece.
    </p>
    <input type="hidden" name="planner_icon" value="">
    <div class="doc-icon-picker">
        <label class="doc-icon-picker__opt {{ $selected === null || $selected === '' ? 'is-selected' : '' }}">
            <input type="radio" name="planner_icon" value="" {{ $selected === null || $selected === '' ? 'checked' : '' }}>
            <span class="doc-icon-picker__glyph"><i class="bi bi-dash-lg"></i></span>
            <span>Brak</span>
        </label>
        @foreach(\App\Enums\DocumentPlannerIcon::cases() as $icon)
            <label class="doc-icon-picker__opt {{ $selected === $icon->value ? 'is-selected' : '' }}">
                <input type="radio" name="planner_icon" value="{{ $icon->value }}" {{ $selected === $icon->value ? 'checked' : '' }}>
                <span class="doc-icon-picker__glyph"><i class="bi {{ $icon->value }}"></i></span>
                <span>{{ $icon->label() }}</span>
            </label>
        @endforeach
    </div>
</div>
