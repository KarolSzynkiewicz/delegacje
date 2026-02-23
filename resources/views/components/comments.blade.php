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
        
        <x-ui.input 
            type="textarea" 
            name="body" 
            :label="$inputLabelText"
            rows="3"
            required
        />
        
        <div class="mt-3">
            <x-ui.button variant="primary" type="submit" action="save">
                {{ $buttonTextValue }}
            </x-ui.button>
        </div>
    </form>

    @php
        // Ensure comments are loaded with user relationship
        $comments = $commentable->comments()->with('user')->orderBy('created_at', 'desc')->get();
    @endphp
    
    @if($comments->count() > 0)
        <div class="comments-list">
            @foreach($comments as $comment)
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
                            <p class="mb-0">{{ nl2br(e($comment->body)) }}</p>
                        </div>
                        <div id="comment-edit-{{ $comment->id }}" style="display: none;">
                            <form action="{{ route('comments.update', $comment) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <x-ui.input 
                                    type="textarea" 
                                    name="body" 
                                    :value="$comment->body"
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
</script>
@endpush
