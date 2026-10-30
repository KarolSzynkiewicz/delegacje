<div class="notification-bell" x-data @click.outside="$wire.open && $wire.set('open', false)">
    <button
        type="button"
        wire:click="toggle"
        class="btn btn-link nav-link position-relative p-1 d-flex align-items-center notification-bell__btn"
        title="Powiadomienia"
        aria-label="Powiadomienia"
        aria-expanded="{{ $open ? 'true' : 'false' }}"
    >
        <i class="bi bi-bell{{ $unreadCount > 0 ? '-fill text-warning' : '' }} fs-5"></i>

        @if($unreadCount > 0)
            <span class="notification-bell__badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    @if($open)
        <div
            class="notification-bell__backdrop d-lg-none"
            wire:click="toggle"
            aria-hidden="true"
        ></div>

        <div
            class="notification-bell__panel dropdown-menu show"
            wire:click.stop
            role="dialog"
            aria-label="Powiadomienia"
        >
            <div class="notification-bell__head d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                <span class="fw-semibold small">Powiadomienia</span>
                <div class="d-flex align-items-center gap-2">
                    @if($unreadCount > 0)
                        <button type="button" class="btn btn-link btn-sm p-0 text-muted" wire:click="markAllRead">
                            Oznacz przeczytane
                        </button>
                    @endif
                    <button
                        type="button"
                        class="btn btn-link btn-sm p-0 text-muted d-lg-none"
                        wire:click="toggle"
                        aria-label="Zamknij"
                    >
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>

            @if($notifications->isEmpty())
                <div class="notification-bell__empty px-3 py-4 text-center text-muted small">
                    <i class="bi bi-bell-slash d-block fs-3 mb-1"></i>
                    Brak powiadomień
                </div>
            @else
                <ul class="notification-bell__list list-unstyled mb-0">
                    @foreach($notifications as $n)
                        @php $read = $n->read_at !== null; @endphp
                        <li @class(['notification-bell__item px-3 py-2 border-bottom', 'opacity-50' => $read])>
                            @include('notifications._item', ['n' => $n, 'compact' => true])
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="notification-bell__foot px-3 py-2 border-top text-center">
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="{{ route('notifications.index') }}" class="small text-decoration-none notification-bell__link">
                        <i class="bi bi-inbox me-1"></i>Zobacz wszystkie
                    </a>
                    <a href="{{ route('tasks.index', ['myTasksOnly' => 'true']) }}" class="small text-decoration-none notification-bell__link">
                        <i class="bi bi-list-check me-1"></i>Moje zadania
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
