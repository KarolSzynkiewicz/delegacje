<div class="position-relative" x-data @click.outside="$wire.open && $wire.set('open', false)">
    {{-- Przycisk dzwonka --}}
    <button
        type="button"
        wire:click="toggle"
        class="btn btn-link nav-link position-relative p-1 d-flex align-items-center"
        title="Powiadomienia"
        aria-label="Powiadomienia"
    >
        <i class="bi bi-bell{{ $unreadCount > 0 ? '-fill text-warning' : '' }} fs-5"></i>

        @if($unreadCount > 0)
            <span
                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                style="font-size:.65rem;min-width:1.2rem;"
            >
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown z listą --}}
    @if($open)
        <div
            class="dropdown-menu show position-absolute end-0 mt-1 p-0 border-0"
            style="
                width: 22rem;
                z-index: 1080;
                top: 100%;
                background: var(--bg-card);
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
                border: 1px solid var(--glass-border);
                border-radius: 20px;
                color: var(--text-main);
                box-shadow: 0 18px 55px rgba(0,0,0,.45);
            "
            wire:click.stop
        >
            <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom" style="border-color: var(--glass-border) !important;">
                <span class="fw-semibold small">Powiadomienia</span>
            </div>

            @if($notifications->isEmpty())
                <div class="px-3 py-4 text-center text-muted small">
                    <i class="bi bi-bell-slash d-block fs-3 mb-1"></i>
                    Brak powiadomień
                </div>
            @else
                <ul class="list-unstyled mb-0" style="max-height:22rem;overflow-y:auto;">
                    @foreach($notifications as $n)
                        @php
                            $data = $n->data;
                            $url  = $data['task_url'] ?? $data['url'] ?? null;
                            $read = $n->read_at !== null;
                        @endphp
                        <li
                            @class(['px-3 py-2 border-bottom', 'opacity-50' => $read])
                            style="border-color: var(--glass-border) !important;"
                        >
                            <div class="d-flex align-items-start gap-2">
                                <i @class([
                                    'bi flex-shrink-0 mt-1',
                                    'bi-person-check-fill text-primary' => ($data['type'] ?? '') === 'task_assigned',
                                    'bi-chat-quote-fill text-info'       => ($data['type'] ?? '') === 'comment_mentioned',
                                    'bi-chat-left-text-fill text-success' => ($data['type'] ?? '') === 'task_comment_added',
                                    'bi-list-check text-info'            => ($data['type'] ?? '') === 'subtask_mentioned',
                                    'bi-bell-fill text-secondary'        => !in_array($data['type'] ?? '', ['task_assigned', 'comment_mentioned', 'task_comment_added', 'subtask_mentioned']),
                                ])></i>
                                <div class="min-w-0 flex-grow-1">
                                    <p class="mb-0 small lh-sm">
                                        {{ $data['message'] ?? 'Powiadomienie' }}
                                        @if($url)
                                            — <a href="{{ $url }}" class="fw-semibold text-decoration-none" style="color: var(--text-main);">
                                                {{ $data['task_name'] ?? $data['context_name'] ?? $data['subtask_name'] ?? 'otwórz' }}
                                            </a>
                                        @endif
                                    </p>
                                    <p class="mb-0 text-muted" style="font-size:.7rem;">
                                        {{ $n->created_at->diffForHumans() }}
                                        @if(! $read)
                                            · <span class="text-warning fw-semibold">nowe</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="px-3 py-2 border-top text-center" style="border-color: var(--glass-border) !important;">
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="{{ route('notifications.index') }}" class="small text-decoration-none" style="color: var(--text-main);">
                        <i class="bi bi-inbox me-1"></i>Zobacz wszystkie
                    </a>
                    <a href="{{ route('tasks.index', ['myTasksOnly' => 'true']) }}" class="small text-decoration-none" style="color: var(--text-main);">
                        <i class="bi bi-list-check me-1"></i>Moje zadania
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
