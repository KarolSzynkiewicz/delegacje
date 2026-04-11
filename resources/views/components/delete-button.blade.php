@props([
    'action',
    'size' => 'sm',
    'title' => 'Usuń',
    'message' => 'Czy na pewno chcesz usunąć ten element?',
])

@php
    use Illuminate\Support\Js;

    $sizeClass = $size === 'sm' ? 'btn-sm' : ($size === 'lg' ? 'btn-lg' : '');
@endphp

<form action="{{ $action }}" method="POST" class="d-inline">
    @csrf
    @method('DELETE')
    <x-ui.button
        variant="danger"
        type="submit"
        title="{{ $title }}"
        class="{{ $sizeClass }}"
        onclick='return confirm({{ Js::from($message) }})'
    >
        <i class="bi bi-trash"></i>
        @if($slot->isNotEmpty())
            <span class="ms-1">{{ $slot }}</span>
        @endif
    </x-ui.button>
</form>
