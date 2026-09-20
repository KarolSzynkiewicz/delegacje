@props([
    'employees' => [],
    'users' => [],
    'size' => '30px',
    'max' => 4,
])

@php
    $items = collect($users)->filter()->isNotEmpty()
        ? collect($users)->filter()->values()
        : collect($employees)->filter()->values();
    $visible = $items->take($max);
    $overflow = $items->count() - $visible->count();
    $isUser = collect($users)->filter()->isNotEmpty();
@endphp

@if($items->isEmpty())
    <span class="text-muted small">—</span>
@else
    <div class="avatar-stack">
        @foreach($visible as $person)
            @php
                $label = $isUser ? $person->name : $person->full_name;
                $initials = $isUser
                    ? $person->initials
                    : mb_substr($person->first_name ?? '', 0, 1).mb_substr($person->last_name ?? '', 0, 1);
            @endphp
            <span class="avatar-stack__item" title="{{ $label }}">
                <x-ui.avatar
                    :image-url="$person->image_url"
                    :alt="$label"
                    :initials="$initials"
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
