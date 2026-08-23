@props([
    'employees' => [],
    'size' => '30px',
    'max' => 4,
])

@php
    $items = collect($employees)->filter()->values();
    $visible = $items->take($max);
    $overflow = $items->count() - $visible->count();
@endphp

@if($items->isEmpty())
    <span class="text-muted small">—</span>
@else
    <div class="avatar-stack">
        @foreach($visible as $employee)
            <span class="avatar-stack__item" title="{{ $employee->full_name }}">
                <x-ui.avatar
                    :image-url="$employee->image_url"
                    :alt="$employee->full_name"
                    :initials="mb_substr($employee->first_name ?? '', 0, 1) . mb_substr($employee->last_name ?? '', 0, 1)"
                    :size="$size"
                    :border="false"
                />
            </span>
        @endforeach
        @if($overflow > 0)
            <span
                class="avatar-stack__more"
                style="width: {{ $size }}; height: {{ $size }};"
                title="+{{ $overflow }} więcej"
            >+{{ $overflow }}</span>
        @endif
    </div>
@endif
