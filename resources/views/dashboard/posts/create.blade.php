<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Nowy wątek">
            <x-slot:left>
                <x-ui.button variant="ghost" href="{{ route('dashboard') }}" action="back">Tablica</x-ui.button>
            </x-slot:left>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Nowy wątek">
                <form method="POST" action="{{ route('dashboard.posts.store') }}" enctype="multipart/form-data">
                    @csrf
                    @include('dashboard.posts._form', ['suggestTags' => $tags])
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
