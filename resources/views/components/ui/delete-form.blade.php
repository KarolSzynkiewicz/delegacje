@props([
    'url' => null,
    'message' => 'Czy na pewno chcesz usunąć ten element?',
    'class' => '',
    'buttonClass' => 'btn-sm',
    'buttonVariant' => 'danger',
    'buttonText' => 'Usuń',
])

@php
    use Illuminate\Support\Js;
@endphp

<form action="{{ $url }}" method="POST" class="d-inline">
    @csrf
    @method('DELETE')
    <x-ui.button
        variant="{{ $buttonVariant }}"
        type="submit"
        class="{{ $buttonClass }} {{ $class }}"
        onclick='return confirm({{ Js::from($message) }})'
    >
        {{ $buttonText }}
    </x-ui.button>
</form>
