<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Tablica">
            <x-slot:left>
                <x-ui.button variant="ghost" href="{{ route('dashboard.overview') }}">
                    Przegląd systemu
                </x-ui.button>
            </x-slot:left>
            <x-slot:right>
                <x-ui.button variant="primary" href="{{ route('dashboard.posts.create') }}" action="create">
                    Nowy wątek
                </x-ui.button>
            </x-slot:right>
        </x-ui.page-header>
    </x-slot>

    @if(session('success'))
        <x-ui.alert variant="success" dismissible class="mb-4">{{ session('success') }}</x-ui.alert>
    @endif

    <form method="GET" action="{{ route('dashboard') }}" class="forum-toolbar">
        <label class="forum-search">
            <i class="bi bi-search"></i>
            <input
                type="search"
                name="q"
                value="{{ $q }}"
                placeholder="Szukaj po tytule, treści, tagu…"
                aria-label="Szukaj wątków"
            >
        </label>
        @if($tagSlug)
            <input type="hidden" name="tag" value="{{ $tagSlug }}">
        @endif
        <x-ui.button variant="ghost" type="submit">Szukaj</x-ui.button>
        @if($q !== '' || $tagSlug !== '')
            <x-ui.button variant="ghost" href="{{ route('dashboard') }}">Wyczyść</x-ui.button>
        @endif
    </form>

    @if($tags->isNotEmpty())
        <div class="forum-tag-cloud" aria-label="Tagi">
            @foreach($tags as $tag)
                <a
                    href="{{ route('dashboard', array_filter(['q' => $q ?: null, 'tag' => $tag->slug])) }}"
                    class="forum-tag {{ $tagSlug === $tag->slug ? 'is-active' : '' }}"
                >
                    #{{ $tag->name }}
                    <span class="forum-tag__count">{{ $tag->posts_count }}</span>
                </a>
            @endforeach
        </div>
    @endif

    @if($posts->isEmpty())
        <x-ui.card>
            <x-ui.empty-state
                icon="chat-square-text"
                :message="$q !== '' || $tagSlug !== '' ? 'Brak wątków dla tego filtra' : 'Tablica jest pusta — napisz pierwszy wątek'"
                :has-filters="$q !== '' || $tagSlug !== ''"
                :clear-filters-action="$q !== '' || $tagSlug !== '' ? route('dashboard') : null"
            >
                @if($q === '' && $tagSlug === '')
                    <x-ui.button variant="primary" href="{{ route('dashboard.posts.create') }}" action="create" class="mt-2">
                        Nowy wątek
                    </x-ui.button>
                @endif
            </x-ui.empty-state>
        </x-ui.card>
    @else
        <div class="d-flex flex-column gap-3">
            @foreach($posts as $post)
                @include('dashboard.posts._card', ['post' => $post])
            @endforeach
        </div>
        <div class="mt-4">
            {{ $posts->links() }}
        </div>
    @endif
</x-app-layout>
