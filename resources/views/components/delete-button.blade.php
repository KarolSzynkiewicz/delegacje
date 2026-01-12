@props([
    'action',
    'size' => 'sm',
    'title' => 'Usuń',
    'message' => 'Czy na pewno chcesz usunąć ten element?',
])

@php
    $sizeClass = $size === 'sm' ? 'btn-sm' : ($size === 'lg' ? 'btn-lg' : '');
@endphp

<form action="{{ $action }}" 
      method="POST" 
      class="d-inline"
      onsubmit="return confirm('{{ $message }}')">
    @csrf
    @method('DELETE')
    <x-ui.button variant="danger" type="submit" title="{{ $title }}" class="{{ $sizeClass }}">
        <i class="bi bi-trash"></i>
        @if($slot->isNotEmpty())
            <span class="ms-1">{{ $slot }}</span>
        @endif
    </x-ui.button>
</form>
