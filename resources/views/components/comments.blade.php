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
    <form action="{{ route('comments.store') }}" method="POST" class="mb-4">
        @csrf
        <input type="hidden" name="commentable_type" value="{{ $commentableType->value }}">
        <input type="hidden" name="commentable_id" value="{{ $commentable->id }}">

        <div class="mb-3 position-relative" x-data="mentionAutocomplete()">
            <label class="form-label">{{ $inputLabelText }}</label>
            <textarea
                name="body"
                rows="3"
                class="form-control"
                required
                placeholder="Możesz wspomnieć o użytkowniku pisząc @NazwaUzytkownika"
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

        <div class="mt-1">
            <x-ui.button variant="primary" type="submit" action="save">
                {{ $buttonTextValue }}
            </x-ui.button>
        </div>
    </form>

    @php
        // Ensure comments are loaded with user relationship
        $comments = $commentable->comments()->with('user')->orderBy('created_at', 'desc')->get();
        $knownUsersForHighlight = \App\Models\User::orderBy('name')->get()
            ->map(fn($u) => ['name' => $u->name, 'initials' => $u->initials])
            ->all();
    @endphp
    
    @if($comments->count() > 0)
        <div class="comments-list">
            @foreach($comments as $comment)
                @php
                    // Support both stored newlines and stored <br> tags (historical data)
                    $commentBodyForDisplay = preg_replace('/<br\\s*\\/?\\s*>/i', "\n", (string) $comment->body);
                    $commentBodyForEdit = $commentBodyForDisplay;
                @endphp
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center">
                                <x-ui.avatar :name="$comment->user->name" size="sm" class="me-2" />
                                <div>
                                    <strong>{{ $comment->user->name }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $comment->created_at->format('d.m.Y H:i') }}</small>
                                </div>
                            </div>
                            @if($comment->user_id === auth()->id() || auth()->user()->isAdmin())
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="editComment({{ $comment->id }})">
                                        Edytuj
                                    </button>
                                    <x-ui.delete-form 
                                        :url="route('comments.destroy', $comment)"
                                        message="Czy na pewno chcesz usunąć ten komentarz?"
                                        class="d-inline"
                                    />
                                </div>
                            @endif
                        </div>
                        <div id="comment-body-{{ $comment->id }}">
                            <p class="mb-0">{!! \App\Services\UserMentionService::highlightMentions(
                                nl2br(e($commentBodyForDisplay)),
                                $knownUsersForHighlight
                            ) !!}</p>
                        </div>
                        <div id="comment-edit-{{ $comment->id }}" style="display: none;">
                            <form action="{{ route('comments.update', $comment) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <x-ui.input 
                                    type="textarea" 
                                    name="body" 
                                    :value="$commentBodyForEdit"
                                    rows="3"
                                />
                                <div class="mt-2">
                                    <x-ui.button variant="primary" type="submit" class="btn-sm">Zapisz</x-ui.button>
                                    <x-ui.button variant="ghost" type="button" class="btn-sm" onclick="cancelEdit({{ $comment->id }})">Anuluj</x-ui.button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
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
        document.getElementById('comment-body-' + commentId).style.display = 'none';
        document.getElementById('comment-edit-' + commentId).style.display = 'block';
    }

    function cancelEdit(commentId) {
        document.getElementById('comment-body-' + commentId).style.display = 'block';
        document.getElementById('comment-edit-' + commentId).style.display = 'none';
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
