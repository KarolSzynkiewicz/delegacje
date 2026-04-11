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

        <div class="mb-3 position-relative" x-data="mentionAutocomplete()">
            <label class="form-label">{{ $inputLabelText }}</label>
            <textarea
                name="body"
                rows="3"
                class="form-control"
                placeholder="Możesz wspomnieć o użytkowniku pisząc @NazwaUzytkownika (treść lub załącznik — wymagane jest przynajmniej jedno)"
                x-ref="textarea"
                @input="onInput($event)"
                @keydown.escape="close()"
            ></textarea>

            {{-- Dropdown z podpowiedziami --}}
            <ul
                x-show="show && results.length > 0"
                x-cloak
                class="dropdown-menu show list-unstyled position-absolute mb-0 py-1"
                style="z-index:1090;min-width:16rem;max-height:14rem;overflow-y:auto;top:100%;left:0;right:auto;"
            >
                <template x-for="(user, idx) in results" :key="user.name">
                    <li>
                        <button
                            type="button"
                            class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 text-start w-100"
                            :class="idx === activeIdx ? 'active' : ''"
                            @click="selectUser(user)"
                            @mouseenter="activeIdx = idx"
                        >
                            <span
                                class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-25 text-primary fw-semibold flex-shrink-0"
                                style="width:1.75rem;height:1.75rem;font-size:.65rem;"
                                x-text="user.initials"
                            ></span>
                            <span class="small fw-medium" x-text="user.name"></span>
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
            ->with(['user', 'attachments', 'likes.user'])
            ->withCount('likes')
            ->withExists(['likes as liked_by_me' => fn ($q) => $q->where('user_id', auth()->id())])
            ->orderBy('created_at', 'desc')
            ->get();
        $commentRoots = $allCommentsFlat->whereNull('parent_id')->values();
        $childrenOf = $allCommentsFlat->whereNotNull('parent_id')->groupBy('parent_id');
        $knownUsersForHighlight = \App\Models\User::orderBy('name')->get()
            ->map(fn ($u) => ['name' => $u->name, 'initials' => $u->initials])
            ->all();
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

    // Alpine.js component dla @mention autocomplete
    function mentionAutocomplete() {
        const allUsers = @json(\App\Models\User::orderBy('name')->get()->map(fn($u) => [
            'name'     => $u->name,
            'initials' => $u->initials,
        ]));

        return {
            show: false,
            results: [],
            activeIdx: 0,
            query: '',
            mentionStart: -1,

            onInput(event) {
                const ta = this.$refs.textarea;
                const pos = ta.selectionStart;
                const text = ta.value.substring(0, pos);

                // Znajdź ostatnie @ poprzedzone spacją lub początkiem tekstu
                const triggerMatch = text.match(/(^|(?<=\s))@(\S*)$/u);

                if (! triggerMatch) { this.close(); return; }

                // Indeks znaku @ w oryginalnym tekście
                const fragment = triggerMatch[2]; // to co po @
                this.mentionStart = pos - fragment.length - 1; // pozycja @

                if (fragment.length === 0) { this.close(); return; }

                const q = fragment.toLowerCase();
                this.results = allUsers.filter(u => u.name.toLowerCase().includes(q)).slice(0, 8);
                this.activeIdx = 0;
                this.show = this.results.length > 0;
            },

            selectUser(user) {
                const ta = this.$refs.textarea;
                const before = ta.value.substring(0, this.mentionStart);
                const after  = ta.value.substring(ta.selectionStart);
                ta.value = before + '@' + user.name + ' ' + after;
                // Przesuń kursor za wstawioną wzmiankę
                const newPos = before.length + user.name.length + 2;
                ta.setSelectionRange(newPos, newPos);
                ta.focus();
                this.close();
            },

            close() {
                this.show = false;
                this.results = [];
                this.mentionStart = -1;
            },
        };
    }
</script>
@endpush
