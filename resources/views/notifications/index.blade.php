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
                            $read = $n->read_at !== null;
                        @endphp

                        <li @class(['px-3 py-3 border-bottom', 'opacity-75' => $read])>
                            @include('notifications._item', ['n' => $n, 'compact' => false, 'largeIcon' => true])
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

