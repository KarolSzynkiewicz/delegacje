@props([
    'commentable',
    'label' => null,
    'inputLabel' => null,
    'buttonText' => null,
])

@php
    $commentableType = \App\Enums\CommentableType::fromModel($commentable);
    
    $isTask = $commentable instanceof \App\Models\ProjectTask
        && ! $commentable->procedure_run_id
        && ! $commentable->isCallback();
    
    $cardLabel = $label ?? ($isTask ? 'Dziennik operacyjny' : 'Komentarze');
    $inputLabelText = $inputLabel ?? ($isTask ? 'Dodaj raport z działania' : 'Dodaj komentarz');
    $buttonTextValue = $buttonText ?? ($isTask ? 'Dodaj raport z działania' : 'Dodaj komentarz');

    $commentAutocompleteUsers = \App\Models\User::orderedDirectory()
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

<x-ui.card class="comments-card">
    <span class="card-label">
        @if($isTask && !$label)
            <i class="bi bi-briefcase me-1"></i>
        @endif
        {{ $cardLabel }}
    </span>
    <form action="{{ route('comments.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="commentable_type" value="{{ $commentableType->value }}">
        <input type="hidden" name="commentable_id" value="{{ $commentable->id }}">
        <x-comment-composer
            :placeholder="$isTask ? '@osoba, #1 albo załącznik…' : '@osoba albo załącznik…'"
            :autocomplete-payload="$commentAutocompletePayload"
            :submit-title="$buttonTextValue"
            :file-input-id="'comment-files-'.$commentableType->value.'-'.$commentable->id"
        />
    </form>

    @php
        $allCommentsFlat = $commentable->comments()
            ->with([
                'user',
                'attachments',
                'likes.user',
                'parent.user',
                'parent.attachments',
                'mentions' => fn ($q) => $q->where('assigned_to', auth()->id()),
            ])
            ->withCount('likes')
            ->withExists(['likes as liked_by_me' => fn ($q) => $q->where('user_id', auth()->id())])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();
        $knownUsersForHighlight = $commentAutocompleteUsers;
        if ($commentable instanceof \App\Models\ProjectTask) {
            $commentable->loadMissing('subtasks');
        }
    @endphp

    @if($allCommentsFlat->count() > 0)
        <div class="comments-thread">
            @foreach($allCommentsFlat as $comment)
                @include('components.comment-node', [
                    'comment' => $comment,
                    'commentable' => $commentable,
                    'commentableTypeValue' => $commentableType->value,
                    'knownUsersForHighlight' => $knownUsersForHighlight,
                    'commentAutocompletePayload' => $commentAutocompletePayload,
                ])
            @endforeach
        </div>
    @else
        <x-ui.empty-state
            icon="chat-dots"
            :message="$isTask ? 'Brak raportów z działania' : 'Brak komentarzy'"
            class="py-3"
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
            files: [],

            onFiles(event) {
                this.files = Array.from(event.target.files || []);
            },

            fileSummary() {
                if (this.files.length === 0) {
                    return '';
                }
                if (this.files.length === 1) {
                    return this.files[0].name;
                }
                const n = this.files.length;

                return n + (n < 5 ? ' pliki' : ' plików');
            },

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
