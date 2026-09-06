<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edycja wątku">
            <x-slot:left>
                <x-ui.button variant="ghost" href="{{ route('dashboard.posts.show', $post) }}" action="back">Wątek</x-ui.button>
            </x-slot:left>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj wątek">
                <form method="POST" action="{{ route('dashboard.posts.update', $post) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    @include('dashboard.posts._form', ['post' => $post, 'suggestTags' => $tags])
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
