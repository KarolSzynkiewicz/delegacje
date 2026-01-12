@props([
    'url' => null,
    'message' => 'Czy na pewno chcesz usunąć ten element?',
    'class' => '',
    'buttonClass' => 'btn-sm',
    'buttonVariant' => 'danger',
    'buttonText' => 'Usuń',
])

<form action="{{ $url }}" method="POST" class="d-inline">
    @csrf
    @method('DELETE')
    <x-ui.button 
        variant="{{ $buttonVariant }}" 
        type="submit" 
        class="{{ $buttonClass }} {{ $class }}"
        onclick="return confirm('{{ $message }}')"
    >
        {{ $buttonText }}
    </x-ui.button>
</form>
