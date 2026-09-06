@php
    $liked = $post->isLikedBy(auth()->user());
    $likeNames = $likers->pluck('name')->filter()->values();
    $likeTitle = $likeNames->isNotEmpty()
        ? 'Polubili: '.$likeNames->implode(', ')
        : ($liked ? 'Cofnij polubienie' : 'Polub');
    $viewerLimit = 8;
    $visibleViewers = $viewers->take($viewerLimit);
    $extraViewers = $viewers->count() - $visibleViewers->count();
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Wątek">
            <x-slot:left>
                <x-ui.button variant="ghost" href="{{ route('dashboard') }}" action="back">Tablica</x-ui.button>
            </x-slot:left>
            @if($post->canBeManagedBy(auth()->user()))
                <x-slot:right>
                    <x-ui.button variant="ghost" href="{{ route('dashboard.posts.edit', $post) }}" action="edit">Edytuj</x-ui.button>
                    <x-ui.delete-form
                        :url="route('dashboard.posts.destroy', $post)"
                        message="Usunąć ten wątek wraz z komentarzami?"
                    />
                </x-slot:right>
            @endif
        </x-ui.page-header>
    </x-slot>

    @if(session('success'))
        <x-ui.alert variant="success" dismissible class="mb-4">{{ session('success') }}</x-ui.alert>
    @endif

    <article class="forum-post forum-post--open mb-4">
        @if($post->cover_url)
            <div class="forum-post__cover">
                <img src="{{ $post->cover_url }}" alt="">
            </div>
        @endif

        <div class="forum-post__topline">
            <div class="forum-post__author">
                <x-ui.avatar
                    :image-url="$post->user?->image_url"
                    :alt="$post->user?->name"
                    :initials="$post->user?->initials ?? '?'"
                    size="44px"
                    :border="false"
                />
                <div>
                    <div class="forum-post__name">{{ $post->user?->name ?? 'Konto usunięte' }}</div>
                    <div class="forum-post__time font-mono">{{ $post->created_at?->format('d.m.Y · H:i') }}</div>
                </div>
            </div>
            <div class="forum-post__tags">
                @if($post->pinned)
                    <span class="forum-post__pin"><i class="bi bi-pin-angle-fill"></i> Przypięty</span>
                @endif
                @foreach($post->tags as $tag)
                    <a href="{{ route('dashboard', ['tag' => $tag->slug]) }}" class="forum-tag">#{{ $tag->name }}</a>
                @endforeach
            </div>
        </div>

        <h1 class="forum-post__title forum-post__title-lg">{{ $post->title }}</h1>
        <div class="forum-post__article">
            @foreach($post->blocks() as $block)
                @if(($block['type'] ?? '') === 'image')
                    <figure class="forum-post__figure">
                        <img src="{{ asset('storage/'.$block['path']) }}" alt="">
                    </figure>
                @else
                    <div class="forum-post__prose">
                        {!! $post->renderTextBlock($block['content'] ?? '') !!}
                    </div>
                @endif
            @endforeach
        </div>

        <div class="forum-post__footer pt-3" style="border-top: 1px solid var(--glass-border);">
            <form action="{{ route('dashboard.posts.like', $post) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="forum-action-btn {{ $liked ? 'is-on' : '' }}" title="{{ $likeTitle }}">
                    <i class="bi {{ $liked ? 'bi-heart-fill' : 'bi-heart' }}"></i>
                    {{ $post->likes_count ?? 0 }}
                </button>
            </form>
            <span class="forum-post__stat">
                <i class="bi bi-chat-dots"></i>
                {{ $post->comments_count ?? 0 }}
            </span>
            <div class="forum-people ms-auto">
                <span class="forum-post__stat">
                    <i class="bi bi-eye"></i>
                    {{ $post->views_count ?? $viewers->count() }}
                </span>
                @if($visibleViewers->isNotEmpty())
                    <span class="forum-people__list" title="Kto widział">
                        @foreach($visibleViewers as $viewer)
                            <span class="forum-people__av" title="{{ $viewer->name }}">
                                <x-ui.avatar
                                    :image-url="$viewer->image_url"
                                    :alt="$viewer->name"
                                    :initials="$viewer->initials"
                                    size="28px"
                                    :border="false"
                                />
                            </span>
                        @endforeach
                    </span>
                    @if($extraViewers > 0)
                        <span class="forum-people__more">+{{ $extraViewers }}</span>
                    @endif
                @endif
            </div>
        </div>
    </article>

    <x-comments :commentable="$post" />
</x-app-layout>
