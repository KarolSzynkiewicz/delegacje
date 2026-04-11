<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <h2 class="fw-semibold fs-4 mb-0">Powiadomienia</h2>
            <x-ui.button variant="ghost" href="{{ route('tasks.index', ['myTasksOnly' => 'true']) }}" class="btn-sm">
                <i class="bi bi-list-check me-1"></i> Moje zadania
            </x-ui.button>
        </div>
    </x-slot>

    <div class="py-4">
        <x-ui.card>
            @if($notifications->isEmpty())
                <div class="py-5 text-center text-muted">
                    <i class="bi bi-bell-slash d-block fs-2 mb-2"></i>
                    Brak powiadomień
                </div>
            @else
                <ul class="list-unstyled mb-0">
                    @foreach($notifications as $n)
                        @php
                            $data = $n->data ?? [];
                            $url  = $data['resource_url'] ?? $data['task_url'] ?? $data['url'] ?? null;
                            $read = $n->read_at !== null;
                        @endphp

                        <li @class(['px-3 py-3 border-bottom', 'opacity-75' => $read])>
                            <div class="d-flex align-items-start gap-3">
                                <i @class([
                                    'bi fs-5 flex-shrink-0 mt-1',
                                    'bi-person-check-fill text-primary' => ($data['type'] ?? '') === 'task_assigned',
                                    'bi-chat-quote-fill text-info' => ($data['type'] ?? '') === 'comment_mentioned',
                                    'bi-chat-left-text-fill text-success' => ($data['type'] ?? '') === 'task_comment_added',
                                    'bi-list-check text-info' => ($data['type'] ?? '') === 'subtask_mentioned',
                                    'bi-heart-fill text-danger' => ($data['type'] ?? '') === 'comment_liked',
                                    'bi-bell-fill text-secondary' => !in_array($data['type'] ?? '', ['task_assigned', 'comment_mentioned', 'task_comment_added', 'subtask_mentioned', 'comment_liked']),
                                ])></i>

                                <div class="min-w-0 flex-grow-1">
                                    <div class="d-flex align-items-start justify-content-between gap-3">
                                        <div class="min-w-0">
                                            <div class="small lh-sm">
                                                <span class="{{ $read ? 'text-muted' : 'fw-semibold' }}">
                                                    {{ $data['message'] ?? 'Powiadomienie' }}
                                                </span>
                                                @if($url)
                                                    <span class="text-muted">—</span>
                                                    <a href="{{ $url }}" class="text-decoration-none">
                                                        {{ $data['task_name'] ?? $data['context_name'] ?? $data['subtask_name'] ?? 'otwórz' }}
                                                    </a>
                                                @endif
                                            </div>
                                            <div class="text-muted" style="font-size:.8rem;">
                                                {{ $n->created_at->diffForHumans() }}
                                                @if(! $read)
                                                    · <span class="text-warning fw-semibold">nowe</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                @if($notifications->hasPages())
                    <div class="mt-3 pt-3 border-top">
                        {{ $notifications->links() }}
                    </div>
                @endif
            @endif
        </x-ui.card>
    </div>
</x-app-layout>

