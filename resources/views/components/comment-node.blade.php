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
    $mention = $comment->mentionFor(auth()->id());
    $mentionDone = $mention?->isCompleted() ?? false;
    $approval = $comment->approvalFor(auth()->id());
    $canEdit = $comment->user_id === auth()->id();
    $canDelete = $canEdit || auth()->user()->isAdmin();
    $liked = (bool) ($comment->liked_by_me ?? false);
@endphp

<article
    class="comment-item"
    id="comment-{{ $comment->id }}"
    x-data="{ replyOpen: false }"
>
    <div class="comment-item__head">
        <div class="comment-item__who">
            <x-ui.avatar :initials="$comment->user->initials" size="28px" :border="false" />
            <div class="comment-item__meta">
                <span class="comment-item__name">{{ $comment->user->name }}</span>
                <span class="comment-item__time">{{ $comment->created_at->format('d.m.Y H:i') }}</span>
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
            <div class="comments-composer">
                <textarea name="body" rows="2" class="comments-composer-input">{{ $commentBodyForEdit }}</textarea>
                <div class="comments-composer-toolbar">
                    <label class="comments-icon-btn" for="comment-edit-files-{{ $comment->id }}" title="Dodaj załączniki">
                        <i class="bi bi-paperclip"></i>
                    </label>
                    <input
                        id="comment-edit-files-{{ $comment->id }}"
                        type="file"
                        name="attachments[]"
                        class="comments-file-input"
                        multiple
                        accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.txt,.zip,application/pdf,image/*"
                    >
                    <button type="submit" class="comments-icon-btn comments-send-btn" title="Zapisz" aria-label="Zapisz">
                        <i class="bi bi-check-lg"></i>
                    </button>
                    <button type="button" class="comments-icon-btn" title="Anuluj" aria-label="Anuluj" onclick="cancelEdit({{ $comment->id }})">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
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
                :placeholder="$commentable instanceof \App\Models\ProjectTask ? '@osoba, #1 albo załącznik…' : 'Odpowiedź…'"
                :rows="2"
                :autocomplete-payload="$commentAutocompletePayload"
                submit-title="Wyślij odpowiedź"
                :file-input-id="'comment-reply-files-'.$comment->id"
            />
        </form>
    </div>
</article>
