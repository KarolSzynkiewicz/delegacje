@php
    $commentAutocompletePayload = $commentAutocompletePayload ?? ['users' => [], 'subtasks' => []];
    $commentBodyForDisplay = preg_replace('/<br\s*\/?\s*>/i', "\n", (string) ($comment->body ?? ''));
    $commentBodyForEdit = $commentBodyForDisplay;
    $quoted = $comment->parent;
    $commentBodyHtml = \App\Services\UserMentionService::highlightMentions(
        nl2br(e($commentBodyForDisplay)),
        $knownUsersForHighlight
    );
    if ($commentable instanceof \App\Models\ProjectTask) {
        $commentBodyHtml = \App\Services\UserMentionService::highlightSubtaskRefs($commentBodyHtml, $commentable);
    }

    $likersForTooltip = collect();
    if (($comment->likes_count ?? 0) > 0 && $comment->relationLoaded('likes')) {
        $likersForTooltip = $comment->likes
            ->map(fn ($like) => $like->user)
            ->filter()
            ->unique('id')
            ->sortBy(fn ($u) => mb_strtolower($u->name))
            ->values();
    }
    $likeActionHint = ($comment->liked_by_me ?? false) ? 'Cofnij polubienie' : 'Polub';
    $likeButtonTitle = $likersForTooltip->isNotEmpty()
        ? 'Polubili: '.$likersForTooltip->pluck('name')->implode(', ').' — '.$likeActionHint
        : $likeActionHint;
    $viewersForTooltip = collect();
    if ($comment->relationLoaded('views')) {
        $viewersForTooltip = $comment->views
            ->map(fn ($view) => $view->user)
            ->filter()
            ->unique('id')
            ->sortBy(fn ($u) => mb_strtolower($u->name))
            ->values();
    }
    $viewCount = (int) ($comment->views_count ?? $viewersForTooltip->count());
    $viewButtonTitle = $viewersForTooltip->isNotEmpty()
        ? 'Widzieli: '.$viewersForTooltip->pluck('name')->implode(', ')
        : 'Nikt jeszcze nie otworzył tego komentarza';
    $mention = $comment->mentionFor(auth()->id());
    $mentionDone = $mention?->isCompleted() ?? false;
    $approval = $comment->approvalFor(auth()->id());
    $canEdit = $comment->user_id === auth()->id();
    $canDelete = $canEdit || auth()->user()->isAdmin();
    $liked = (bool) ($comment->liked_by_me ?? false);
@endphp

<article
    class="comment-item {{ $comment->pinned ? 'comment-item--pinned' : '' }}"
    id="comment-{{ $comment->id }}"
    x-data="{ replyOpen: false }"
>
    <div class="comment-item__head">
        <div class="comment-item__who">
            <x-ui.avatar :initials="$comment->user->initials" size="28px" :border="false" />
            <div class="comment-item__meta">
                <span class="comment-item__name">{{ $comment->user->name }}</span>
                <span class="comment-item__time">{{ $comment->created_at->format('d.m.Y H:i') }}</span>
                @if($comment->pinned)
                    <span class="comment-item__pin"><i class="bi bi-pin-angle-fill"></i> Przypięty</span>
                @endif
                @if($procedureLink = $comment->procedureSourceCard())
                    <a href="{{ $procedureLink['url'] }}" class="comment-item__proc" title="Otwórz procedurę">
                        <i class="bi bi-diagram-3"></i>
                        <span>{{ $procedureLink['label'] }}</span>
                    </a>
                @endif
            </div>
        </div>
        <div class="comment-item__actions">
            @if($mention)
                <form action="{{ route('comments.mention-task.toggle', $comment) }}" method="POST" class="d-inline">
                    @csrf
                    <button
                        type="submit"
                        class="comments-icon-btn {{ $mentionDone ? 'is-done' : '' }}"
                        title="{{ $mentionDone ? 'Oznacz jako niewykonane' : 'Oznacz jako zrobione' }}"
                        aria-label="{{ $mentionDone ? 'Oznacz jako niewykonane' : 'Oznacz jako zrobione' }}"
                    >
                        <i class="bi bi-check2{{ $mentionDone ? '-square-fill' : '-square' }}"></i>
                    </button>
                </form>
            @endif
            @if($approval)
                <a
                    href="{{ route('approval-requests.show', $approval) }}"
                    class="comments-icon-btn {{ $approval->isDecided() ? 'is-done' : '' }}"
                    title="Wniosek o zatwierdzenie"
                    aria-label="Wniosek o zatwierdzenie"
                >
                    <i class="bi bi-check2-circle"></i>
                </a>
            @endif
            <form action="{{ route('comments.like', $comment) }}" method="POST" class="d-inline">
                @csrf
                <button
                    type="submit"
                    class="comments-icon-btn {{ $liked ? 'is-on' : '' }} {{ (int) ($comment->likes_count ?? 0) > 0 ? 'has-count' : '' }}"
                    title="{{ $likeButtonTitle }}"
                    aria-label="{{ $likeActionHint }}"
                >
                    <i class="bi {{ $liked ? 'bi-heart-fill' : 'bi-heart' }}"></i>
                    @if((int) ($comment->likes_count ?? 0) > 0)
                        <span class="comment-like-count">{{ (int) $comment->likes_count }}</span>
                    @endif
                </button>
            </form>
            <span
                class="comments-icon-btn {{ $viewCount > 0 ? 'has-count' : '' }}"
                title="{{ $viewButtonTitle }}"
                aria-label="Kto widział"
            >
                <i class="bi bi-eye"></i>
                @if($viewCount > 0)
                    <span class="comment-like-count">{{ $viewCount }}</span>
                @endif
            </span>
            <form action="{{ route('comments.pin', $comment) }}" method="POST" class="d-inline">
                @csrf
                <button
                    type="submit"
                    class="comments-icon-btn {{ $comment->pinned ? 'is-pin' : '' }}"
                    title="{{ $comment->pinned ? 'Odepnij z góry sekcji' : 'Przypnij na górze sekcji' }}"
                    aria-label="{{ $comment->pinned ? 'Odepnij' : 'Przypnij' }}"
                >
                    <i class="bi {{ $comment->pinned ? 'bi-pin-angle-fill' : 'bi-pin-angle' }}"></i>
                </button>
            </form>
            <button type="button" class="comments-icon-btn" title="Odpowiedz" aria-label="Odpowiedz" @click="replyOpen = !replyOpen">
                <i class="bi bi-reply"></i>
            </button>
            @if($canEdit)
                <button type="button" class="comments-icon-btn" title="Edytuj" aria-label="Edytuj" onclick="editComment({{ $comment->id }})">
                    <i class="bi bi-pencil"></i>
                </button>
            @endif
            @if($canDelete)
                <form action="{{ route('comments.destroy', $comment) }}" method="POST" class="d-inline" onsubmit="return confirm('Czy na pewno chcesz usunąć ten komentarz wraz z odpowiedziami?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="comments-icon-btn is-danger" title="Usuń" aria-label="Usuń">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div id="comment-body-{{ $comment->id }}">
        @if($comment->parent_id)
            @if($quoted)
                <a href="#comment-{{ $quoted->id }}" class="comment-quote">
                    <span class="comment-quote__author">{{ $quoted->user?->name ?? 'Ktoś' }}</span>
                    <span class="comment-quote__text">{{ $quoted->quoteLabel() }}</span>
                </a>
            @else
                <div class="comment-quote comment-quote--gone">Komentarz usunięty</div>
            @endif
        @endif
        @if(filled($comment->body))
            <div class="comment-item__body comment-body {{ $mentionDone ? 'is-done' : '' }}">{!! $commentBodyHtml !!}</div>
        @endif
        @if($comment->attachments->count() > 0)
            <div class="comment-item__body">
                <x-attachment-list :attachments="$comment->attachments" />
            </div>
        @endif
    </div>

    @if($canEdit)
    <div id="comment-edit-{{ $comment->id }}" class="d-none comment-item__reply">
        <form action="{{ route('comments.update', $comment) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <x-comment-composer
                :value="$commentBodyForEdit"
                :placeholder="$commentable instanceof \App\Models\ProjectTask ? '@osoba, #1 albo załącznik…' : '@osoba, @osoba! albo @osoba?…'"
                :rows="2"
                :autocomplete-payload="$commentAutocompletePayload"
                submit-title="Zapisz"
                :file-input-id="'comment-edit-files-'.$comment->id"
            >
                <x-slot:toolbar>
                    <button type="button" class="comments-icon-btn" title="Anuluj" aria-label="Anuluj" onclick="cancelEdit({{ $comment->id }})">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </x-slot:toolbar>
            </x-comment-composer>
            <x-attachment-list :attachments="$comment->attachments" class="mt-2" />
        </form>
    </div>
    @endif

    <div x-show="replyOpen" x-cloak class="comment-item__reply">
        <form action="{{ route('comments.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="commentable_type" value="{{ $commentableTypeValue }}">
            <input type="hidden" name="commentable_id" value="{{ $commentable->id }}">
            <input type="hidden" name="parent_id" value="{{ $comment->id }}">
            <x-comment-composer
                :placeholder="$commentable instanceof \App\Models\ProjectTask ? '@osoba, #1 albo załącznik…' : '@osoba, @osoba! albo @osoba?…'"
                :rows="2"
                :autocomplete-payload="$commentAutocompletePayload"
                submit-title="Wyślij odpowiedź"
                :file-input-id="'comment-reply-files-'.$comment->id"
            />
        </form>
    </div>
</article>
