@php
    $liked = (bool) ($post->liked_by_me ?? false);
@endphp
<article class="forum-post">
    <div class="forum-post__content">
        <div class="forum-post__topline">
            <div class="forum-post__author">
                <x-ui.avatar
                    :image-url="$post->user?->image_url"
                    :alt="$post->user?->name"
                    :initials="$post->user?->initials ?? '?'"
                    size="36px"
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
                    <a
                        href="{{ route('dashboard', ['tag' => $tag->slug]) }}"
                        class="forum-tag"
                        style="position: relative; z-index: 2"
                    >#{{ $tag->name }}</a>
                @endforeach
            </div>
        </div>

        <h2 class="forum-post__title">
            <a href="{{ route('dashboard.posts.show', $post) }}" class="stretched-link text-decoration-none text-reset">
                {{ $post->title }}
            </a>
        </h2>
        <p class="forum-post__excerpt">{{ $post->excerpt() }}</p>

        <div class="forum-post__footer">
            <span class="forum-post__stat">
                <i class="bi {{ $liked ? 'bi-heart-fill' : 'bi-heart' }}"></i>
                {{ $post->likes_count ?? 0 }}
            </span>
            <span class="forum-post__stat">
                <i class="bi bi-chat-dots"></i>
                {{ $post->comments_count ?? 0 }}
            </span>
            <span class="forum-post__stat">
                <i class="bi bi-eye"></i>
                {{ $post->views_count ?? 0 }}
            </span>
        </div>
    </div>

    @if($post->cover_url)
        <div class="forum-post__cover" aria-hidden="true">
            <img src="{{ $post->cover_url }}" alt="">
        </div>
    @else
        <div class="forum-post__cover forum-post__cover--empty" aria-hidden="true">
            <i class="bi bi-lightbulb"></i>
        </div>
    @endif
</article>
