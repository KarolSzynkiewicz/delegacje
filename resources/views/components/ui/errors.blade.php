@if($errors && $errors->any())
    <x-ui.alert variant="danger" class="mb-4">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-ui.alert>
@endif
