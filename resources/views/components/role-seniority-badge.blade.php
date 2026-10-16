@props([
    'role',
    'seniority' => null,
    'compact' => false,
    'href' => null,
    'row' => false,
])

@php
    $level = $seniority instanceof \App\Enums\RoleSeniority
        ? $seniority
        : \App\Enums\RoleSeniority::fromPivot($seniority ?? $role->pivot->seniority ?? null);
    $tone = $level?->value ?? 'unset';
    $label = $level
        ? $role->name.' · '.$level->shortLabel()
        : $role->name.' · ?';
    $title = $level
        ? $role->name.' — '.$level->label()
        : $role->name.' — nieustalone';
    $tag = $href ? 'a' : 'span';
@endphp

@if($row)
<{{ $tag }}
    @if($href) href="{{ $href }}" @endif
    {{ $attributes->class(['emp-kv__row']) }}
    title="{{ $title }}"
>
    <span class="visually-hidden">{{ $label }}</span>
    <span class="emp-kv__name" aria-hidden="true">{{ $role->name }}</span>
    <span class="emp-kv__tag emp-kv__tag--{{ $tone }}" aria-hidden="true">{{ $level?->shortLabel() ?? '?' }}</span>
</{{ $tag }}>
@else
<{{ $tag }}
    @if($href) href="{{ $href }}" @endif
    {{ $attributes->class([
        'role-ribbon',
        'role-ribbon--'.$tone,
        'role-ribbon--compact' => $compact,
    ]) }}
    title="{{ $title }}"
>
    <span class="visually-hidden">{{ $label }}</span>
    @unless($compact)
        <span class="role-ribbon__name" aria-hidden="true">{{ $role->name }}</span>
    @endunless
    <span class="role-ribbon__tag" aria-hidden="true">
        <span class="role-ribbon__wrap">{{ $level?->shortLabel() ?? '?' }}</span>
    </span>
</{{ $tag }}>
@endif
