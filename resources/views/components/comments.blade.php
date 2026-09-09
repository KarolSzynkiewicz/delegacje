@props([
    'commentable',
    'label' => null,
    'inputLabel' => null,
    'buttonText' => null,
    'embedded' => false,
])

@php
    $commentableType = \App\Enums\CommentableType::fromModel($commentable);
    
    $isTask = $commentable instanceof \App\Models\ProjectTask
        && ! $commentable->procedure_run_id
        && ! $commentable->isCallback()
        && ! $commentable->isMeeting();
    
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

<x-ui.card @class(['comments-card', 'comments-card--embed' => $embedded])>
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
            :placeholder="$isTask ? '@osoba, @osoba! , @osoba? , #1 albo załącznik…' : '@osoba, @osoba! albo @osoba?…'"
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
                'procedureRun.template',
                'procedureRun.task',
                'mentions' => fn ($q) => $q->where('assigned_to', auth()->id()),
                'approvalRequests' => fn ($q) => $q->where('approver_id', auth()->id()),
            ])
            ->withCount('likes')
            ->withExists(['likes as liked_by_me' => fn ($q) => $q->where('user_id', auth()->id())])
            ->orderByDesc('pinned')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();
        $knownUsersForHighlight = $commentAutocompleteUsers;
        if ($commentable instanceof \App\Models\ProjectTask) {
            $commentable->loadMissing('subtasks');
        }
        if (auth()->check() && $allCommentsFlat->isNotEmpty()) {
            \App\Models\CommentView::recordFor($allCommentsFlat->pluck('id'), auth()->user());
            $allCommentsFlat->load(['views.user']);
            foreach ($allCommentsFlat as $viewedComment) {
                $viewedComment->setAttribute('views_count', $viewedComment->views->count());
            }
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

@once
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
</script>
@endpush
@endonce
