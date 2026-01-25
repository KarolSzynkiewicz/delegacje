@props([
    'employee',
    'showPhone' => true,
    'avatarSize' => '40px',
    'avatarShape' => 'circle',
    'link' => true,
    'nameClass' => 'fw-semibold',
])

@php
    $initials = substr($employee->first_name ?? '', 0, 1) . substr($employee->last_name ?? '', 0, 1);
@endphp

<div class="d-flex align-items-start gap-2">
    <x-ui.avatar 
        :image-url="$employee->image_path ? $employee->image_url : null"
        :alt="$employee->full_name"
        :initials="$initials"
        :size="$avatarSize"
        :shape="$avatarShape"
    />
    <div class="flex-grow-1">
        <div>
            @if($link)
                <a href="{{ route('employees.show', $employee) }}" class="{{ $nameClass }} text-decoration-none">
                    {{ $employee->full_name }}
                </a>
            @else
                <span class="{{ $nameClass }}">
                    {{ $employee->full_name }}
                </span>
            @endif
        </div>
        @if($showPhone && $employee->phone)
            <div class="small text-muted mt-1">
                <i class="bi bi-telephone"></i> {{ $employee->phone }}
            </div>
        @endif
    </div>
</div>
