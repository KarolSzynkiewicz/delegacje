@php
    $commentAutocompletePayload = $commentAutocompletePayload ?? ['users' => [], 'subtasks' => []];
    $commentBodyForDisplay = preg_replace('/<br\s*\/?\s*>/i', "\n", (string) ($comment->body ?? ''));
    $commentBodyForEdit = $commentBodyForDisplay;
    $children = ($childrenOf->get($comment->id) ?? collect())->sortBy('created_at');
    $marginRem = min($depth * 1.0, 4.0);
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
    $mentionTask = $comment->mentionTaskFor(auth()->id());
    $mentionTaskDone = $mentionTask?->status === \App\Enums\TaskStatus::COMPLETED;
@endphp

<div
    class="card mb-2 border"
    style="margin-left: {{ $marginRem }}rem;"
    id="comment-{{ $comment->id }}"
    x-data="{ replyOpen: false }"
>
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
            <div class="d-flex align-items-center">
                <x-ui.avatar :name="$comment->user->name" size="sm" class="me-2" />
                <div>
                    <strong>{{ $comment->user->name }}</strong>
                    @if($comment->parent_id)
                        <span class="text-muted small ms-1">· odpowiedź</span>
                    @endif
                    <br>
                    <small class="text-muted">{{ $comment->created_at->format('d.m.Y H:i') }}</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                @if($mentionTask)
                    <form
                        action="{{ route('comments.mention-task.toggle', $comment) }}"
                        method="POST"
                        class="d-inline"
                        id="comment-mention-task-{{ $comment->id }}"
                    >
                        @csrf
                        <button
                            type="submit"
                            class="btn btn-sm btn-outline-secondary flex-shrink-0"
                            style="padding:1px 8px;"
                            title="{{ $mentionTaskDone ? 'Oznacz jako niewykonane' : 'Oznacz jako zrobione' }}"
                            aria-label="{{ $mentionTaskDone ? 'Oznacz jako niewykonane' : 'Oznacz jako zrobione' }}"
                        >
                            <i class="bi bi-check2{{ $mentionTaskDone ? '-square-fill' : '-square' }}"></i>
                        </button>
                    </form>
                @endif
                <form action="{{ route('comments.like', $comment) }}" method="POST" class="d-inline">
                    @csrf
                    <button
                        type="submit"
                        class="btn btn-sm btn-outline-secondary border-0 px-2"
                        title="{{ $likeButtonTitle }}"
                    >
                        <i class="bi {{ ($comment->liked_by_me ?? false) ? 'bi-heart-fill text-danger' : 'bi-heart' }} me-1"></i>
                        <span class="small">{{ (int) ($comment->likes_count ?? 0) }}</span>
                    </button>
                </form>
                <button type="button" class="btn btn-sm btn-outline-primary" @click="replyOpen = !replyOpen">
                    <i class="bi bi-reply me-1"></i>Odpowiedz
                </button>
                @if($comment->user_id === auth()->id() || auth()->user()->isAdmin())
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="editComment({{ $comment->id }})">
                            Edytuj
                        </button>
                        <x-ui.delete-form
                            :url="route('comments.destroy', $comment)"
                            message="Czy na pewno chcesz usunąć ten komentarz wraz z odpowiedziami?"
                            class="d-inline"
                        />
                    </div>
                @endif
            </div>
        </div>

        <div id="comment-body-{{ $comment->id }}">
            @if(filled($comment->body))
                <div class="mb-0 text-break comment-body {{ $mentionTaskDone ? 'text-decoration-line-through text-muted' : '' }}">{!! $commentBodyHtml !!}</div>
            @endif
            <x-attachment-list :attachments="$comment->attachments" :class="filled($comment->body) ? 'mt-2' : ''" />
        </div>

        <div id="comment-edit-{{ $comment->id }}" class="d-none">
            <form action="{{ route('comments.update', $comment) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <x-ui.input
                    type="textarea"
                    name="body"
                    :value="$commentBodyForEdit"
                    rows="3"
                />
                <div class="mt-2 mb-2">
                    <label class="form-label small">Dodaj kolejne załączniki</label>
                    <input type="file" name="attachments[]" class="form-control form-control-sm" multiple accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.txt,.zip,application/pdf,image/*">
                </div>
                <x-attachment-list :attachments="$comment->attachments" class="mt-1" />
                <div class="mt-2">
                    <x-ui.button variant="primary" type="submit" class="btn-sm">Zapisz</x-ui.button>
                    <x-ui.button variant="ghost" type="button" class="btn-sm" onclick="cancelEdit({{ $comment->id }})">Anuluj</x-ui.button>
                </div>
            </form>
        </div>

        <div x-show="replyOpen" x-cloak class="mt-3 pt-3 border-top">
            <form action="{{ route('comments.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="commentable_type" value="{{ $commentableTypeValue }}">
                <input type="hidden" name="commentable_id" value="{{ $commentable->id }}">
                <input type="hidden" name="parent_id" value="{{ $comment->id }}">

                <div class="mb-2 position-relative" x-data="commentBodyAutocomplete(@js($commentAutocompletePayload))">
                    <label class="form-label small">Twoja odpowiedź</label>
                    <textarea
                        name="body"
                        rows="2"
                        class="form-control form-control-sm"
                        placeholder="{{ $commentable instanceof \App\Models\ProjectTask ? '@wzmianka, #1 … lub treść / załącznik' : '@wzmianka lub treść / załącznik' }}"
                        x-ref="textarea"
                        @input="onInput()"
                        @keydown.escape="close()"
                        @keydown.arrow-down="if (show && results.length) { $event.preventDefault(); moveActive(1); }"
                        @keydown.arrow-up="if (show && results.length) { $event.preventDefault(); moveActive(-1); }"
                        @keydown.enter="if (show && results.length) { $event.preventDefault(); pickActive(); }"
                    ></textarea>
                    <ul
                        x-show="show && results.length > 0"
                        x-cloak
                        class="dropdown-menu show list-unstyled position-absolute mb-0 py-1"
                        style="z-index:1090;min-width:14rem;max-height:12rem;overflow-y:auto;top:100%;left:0;"
                    >
                        <template x-for="(item, idx) in results" :key="item.kind === 'user' ? ('u-' + item.name) : ('s-' + item.num)">
                            <li>
                                <button
                                    type="button"
                                    class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 text-start w-100"
                                    :class="idx === activeIdx ? 'active' : ''"
                                    @click="selectItem(item)"
                                    @mouseenter="activeIdx = idx"
                                >
                                    <span x-show="item.kind === 'user'" class="d-flex align-items-center gap-2 w-100 min-w-0">
                                        <span
                                            class="d-inline-flex align-items-center justify-content-center rounded-circle fw-semibold flex-shrink-0"
                                            :class="item.isEveryone ? 'bg-warning bg-opacity-25 text-warning' : 'bg-primary bg-opacity-25 text-primary'"
                                            style="width:1.5rem;height:1.5rem;font-size:.6rem;"
                                            x-text="item.initials"
                                        ></span>
                                        <span class="small fw-medium text-truncate" x-text="item.isEveryone ? '@wszyscy — powiadomienie do wszystkich' : item.name"></span>
                                    </span>
                                    <span x-show="item.kind === 'subtask'" class="d-flex align-items-center gap-2 w-100 min-w-0">
                                        <span class="badge bg-secondary bg-opacity-50 text-body flex-shrink-0" x-text="'#' + item.num"></span>
                                        <span class="small text-truncate" style="max-width:12rem;" x-text="item.name"></span>
                                    </span>
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>
                <div class="mb-2">
                    <input type="file" name="attachments[]" class="form-control form-control-sm" multiple accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.txt,.zip,application/pdf,image/*">
                </div>
                <x-ui.button variant="primary" type="submit" class="btn-sm">Wyślij odpowiedź</x-ui.button>
            </form>
        </div>
    </div>
</div>

@foreach($children as $reply)
    @include('components.comment-node', [
        'comment' => $reply,
        'depth' => $depth + 1,
        'commentable' => $commentable,
        'commentableTypeValue' => $commentableTypeValue,
        'knownUsersForHighlight' => $knownUsersForHighlight,
        'childrenOf' => $childrenOf,
        'commentAutocompletePayload' => $commentAutocompletePayload,
    ])
@endforeach
