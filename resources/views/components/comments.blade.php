@props([
    'commentable',
    'label' => null,
    'inputLabel' => null,
    'buttonText' => null,
])

@php
    $commentableType = \App\Enums\CommentableType::fromModel($commentable);
    
    // Dla ProjectTask używamy innych tekstów
    $isTask = $commentable instanceof \App\Models\ProjectTask;
    
    $cardLabel = $label ?? ($isTask ? 'Dziennik operacyjny' : 'Komentarze');
    $inputLabelText = $inputLabel ?? ($isTask ? 'Dodaj raport z działania' : 'Dodaj komentarz');
    $buttonTextValue = $buttonText ?? ($isTask ? 'Dodaj raport z działania' : 'Dodaj komentarz');

    $commentAutocompleteUsers = \App\Models\User::orderBy('name')
        ->get()
        ->map(fn ($u) => ['name' => $u->name, 'initials' => $u->initials])
        ->values()
        ->all();

    $hashAutocompleteSubtasks = [];
    if ($isTask) {
        $commentable->loadMissing('subtasks');
        $idToNum = $commentable->subtaskDisplayNumbers();
        foreach ($commentable->subtasks->sortBy(['created_at', 'id']) as $st) {
            $hashAutocompleteSubtasks[] = [
                'num' => $idToNum[$st->id],
                'name' => (string) $st->name,
            ];
        }
    }

    $commentAutocompletePayload = [
        'users' => $commentAutocompleteUsers,
        'subtasks' => $hashAutocompleteSubtasks,
    ];
@endphp

<x-ui.card>
    <span class="card-label">
        @if($isTask && !$label)
            <i class="bi bi-briefcase me-1"></i>
        @endif
        {{ $cardLabel }}
    </span>
    <form action="{{ route('comments.store') }}" method="POST" enctype="multipart/form-data" class="mb-4">
        @csrf
        <input type="hidden" name="commentable_type" value="{{ $commentableType->value }}">
        <input type="hidden" name="commentable_id" value="{{ $commentable->id }}">

        <div class="mb-3 position-relative" x-data="commentBodyAutocomplete(@js($commentAutocompletePayload))">
            <label class="form-label">{{ $inputLabelText }}</label>
            <textarea
                name="body"
                rows="3"
                class="form-control"
                placeholder="{{ $isTask ? 'Możesz użyć @NazwaUzytkownika oraz #1, #2 … (odwołanie do podzadania). Treść lub załącznik — wymagane jest przynajmniej jedno' : 'Możesz wspomnieć o użytkowniku pisząc @NazwaUzytkownika (treść lub załącznik — wymagane jest przynajmniej jedno)' }}"
                x-ref="textarea"
                @input="onInput()"
                @keydown.escape="close()"
                @keydown.arrow-down="if (show && results.length) { $event.preventDefault(); moveActive(1); }"
                @keydown.arrow-up="if (show && results.length) { $event.preventDefault(); moveActive(-1); }"
                @keydown.enter="if (show && results.length) { $event.preventDefault(); pickActive(); }"
            ></textarea>

            {{-- Dropdown z podpowiedziami --}}
            <ul
                x-show="show && results.length > 0"
                x-cloak
                class="dropdown-menu show list-unstyled position-absolute mb-0 py-1"
                style="z-index:1090;min-width:16rem;max-height:14rem;overflow-y:auto;top:100%;left:0;right:auto;"
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
                                    style="width:1.75rem;height:1.75rem;font-size:.65rem;"
                                    x-text="item.initials"
                                ></span>
                                <span class="small fw-medium text-truncate" x-text="item.isEveryone ? '@wszyscy — powiadomienie do wszystkich' : item.name"></span>
                            </span>
                            <span x-show="item.kind === 'subtask'" class="d-flex align-items-center gap-2 w-100 min-w-0">
                                <span class="badge bg-secondary bg-opacity-50 text-body flex-shrink-0" x-text="'#' + item.num"></span>
                                <span class="small text-truncate" style="max-width:14rem;" x-text="item.name"></span>
                            </span>
                        </button>
                    </li>
                </template>
            </ul>
        </div>

        <div class="mb-3">
            <label class="form-label">Załączniki (opcjonalnie)</label>
            <input type="file" name="attachments[]" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.txt,.zip,application/pdf,image/*">
            <small class="text-muted d-block mt-1">Do 15 plików, każdy max. 15 MB.</small>
        </div>

        <div class="mt-1">
            <x-ui.button variant="primary" type="submit" action="save">
                {{ $buttonTextValue }}
            </x-ui.button>
        </div>
    </form>

    @php
        $allCommentsFlat = $commentable->comments()
            ->with([
                'user',
                'attachments',
                'likes.user',
                'tasks' => fn ($q) => $q->where('assigned_to', auth()->id()),
            ])
            ->withCount('likes')
            ->withExists(['likes as liked_by_me' => fn ($q) => $q->where('user_id', auth()->id())])
            ->orderBy('created_at', 'desc')
            ->get();
        $commentRoots = $allCommentsFlat->whereNull('parent_id')->values();
        $childrenOf = $allCommentsFlat->whereNotNull('parent_id')->groupBy('parent_id');
        $knownUsersForHighlight = $commentAutocompleteUsers;
        if ($commentable instanceof \App\Models\ProjectTask) {
            $commentable->loadMissing('subtasks');
        }
    @endphp

    @if($allCommentsFlat->count() > 0)
        <div class="comments-list">
            @foreach($commentRoots as $comment)
                @include('components.comment-node', [
                    'comment' => $comment,
                    'depth' => 0,
                    'commentable' => $commentable,
                    'commentableTypeValue' => $commentableType->value,
                    'knownUsersForHighlight' => $knownUsersForHighlight,
                    'childrenOf' => $childrenOf,
                    'commentAutocompletePayload' => $commentAutocompletePayload,
                ])
            @endforeach
        </div>
    @else
        <x-ui.empty-state 
            icon="chat-dots"
            :message="$isTask ? 'Brak raportów z działania' : 'Brak komentarzy'"
        />
    @endif
</x-ui.card>

@push('scripts')
<script>
    function editComment(commentId) {
        const body = document.getElementById('comment-body-' + commentId);
        const edit = document.getElementById('comment-edit-' + commentId);
        if (body) { body.classList.add('d-none'); }
        if (edit) { edit.classList.remove('d-none'); }
    }

    function cancelEdit(commentId) {
        const body = document.getElementById('comment-body-' + commentId);
        const edit = document.getElementById('comment-edit-' + commentId);
        if (body) { body.classList.remove('d-none'); }
        if (edit) { edit.classList.add('d-none'); }
    }

    // Alpine: @wzmianki oraz #n → podpowiedzi podzadań (tylko gdy payload.subtasks niepuste)
    function commentBodyAutocomplete(payload) {
        const allUsers = payload.users || [];
        const subtasks = payload.subtasks || [];

        return {
            show: false,
            results: [],
            activeIdx: 0,
            triggerStart: -1,

            onInput() {
                const ta = this.$refs.textarea;
                const pos = ta.selectionStart;
                const text = ta.value.substring(0, pos);

                const atMatch = text.match(/(^|(?<=\s))@(\S*)$/u);
                if (atMatch) {
                    const fragment = atMatch[2];
                    if (fragment.length > 0) {
                        this.triggerStart = pos - fragment.length - 1;
                        const q = fragment.toLowerCase();
                        const userResults = allUsers
                            .filter((u) => u.name.toLowerCase().includes(q))
                            .slice(0, 7)
                            .map((u) => ({ kind: 'user', name: u.name, initials: u.initials }));
                        const wszyscyResults = 'wszyscy'.startsWith(q)
                            ? [{ kind: 'user', name: 'wszyscy', initials: '★', isEveryone: true }]
                            : [];
                        this.results = [...wszyscyResults, ...userResults];
                        this.activeIdx = 0;
                        this.show = this.results.length > 0;
                        return;
                    }
                }

                const hashMatch = subtasks.length ? text.match(/(^|(?<=\s))#(\d*)$/u) : null;
                if (hashMatch) {
                    const fragment = hashMatch[2];
                    this.triggerStart = pos - fragment.length - 1;
                    const q = fragment;
                    this.results = subtasks
                        .filter((s) => q === '' || String(s.num).startsWith(q))
                        .slice(0, 8)
                        .map((s) => ({ kind: 'subtask', num: s.num, name: s.name }));
                    this.activeIdx = 0;
                    this.show = this.results.length > 0;
                    return;
                }

                this.close();
            },

            moveActive(delta) {
                if (!this.show || this.results.length === 0) {
                    return;
                }
                const n = this.results.length;
                this.activeIdx = (this.activeIdx + delta + n) % n;
            },

            pickActive() {
                if (!this.show || this.results.length === 0) {
                    return;
                }
                this.selectItem(this.results[this.activeIdx]);
            },

            selectItem(item) {
                const ta = this.$refs.textarea;
                const before = ta.value.substring(0, this.triggerStart);
                const after = ta.value.substring(ta.selectionStart);
                if (item.kind === 'user') {
                    ta.value = before + '@' + item.name + ' ' + after;
                    const newPos = before.length + item.name.length + 2;
                    ta.setSelectionRange(newPos, newPos);
                } else {
                    ta.value = before + '#' + item.num + ' ' + after;
                    const newPos = before.length + String(item.num).length + 2;
                    ta.setSelectionRange(newPos, newPos);
                }
                ta.focus();
                this.close();
            },

            close() {
                this.show = false;
                this.results = [];
                this.triggerStart = -1;
            },
        };
    }
</script>
@endpush
