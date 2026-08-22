@php
    $isProcedure = $task->isProcedure();
    $isCallback = $task->isCallback();
    $run = $task->procedureRun;
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$task->name">
            <x-slot name="left">
                <x-ui.button variant="ghost" href="{{ route('tasks.home') }}" action="back">
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                @if($isProcedure && $run)
                    <x-ui.badge variant="accent">Procedura</x-ui.badge>
                    <x-ui.badge variant="{{ $run->status->badgeVariant() }}">{{ $run->status->label() }}</x-ui.badge>
                @elseif($isCallback)
                    <x-ui.badge variant="accent">Oddzwonienie</x-ui.badge>
                    <x-ui.badge variant="{{ $task->status === \App\Enums\TaskStatus::COMPLETED ? 'success' : 'warning' }}">
                        {{ $task->status === \App\Enums\TaskStatus::COMPLETED ? 'Zrobione' : 'Do zrobienia' }}
                    </x-ui.badge>
                @else
                    @php
                        $headerStatusVariant = match($task->status) {
                            \App\Enums\TaskStatus::PENDING => 'warning',
                            \App\Enums\TaskStatus::IN_PROGRESS => 'info',
                            \App\Enums\TaskStatus::COMPLETED => 'success',
                            \App\Enums\TaskStatus::CANCELLED => 'danger',
                        };
                    @endphp
                    <x-ui.badge variant="{{ $headerStatusVariant }}">{{ $task->status->label() }}</x-ui.badge>
                @endif

                <x-ui.button
                    variant="ghost"
                    href="{{ route('tasks.edit', $task) }}"
                    routeName="tasks.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>

                @if($isProcedure && $run && $run->status === \App\Enums\ProcedureRunStatus::IN_PROGRESS)
                    <form action="{{ route('procedure-runs.abandon', $run) }}" method="POST" class="d-inline">
                        @csrf
                        <x-ui.button
                            variant="danger"
                            type="submit"
                            title="Porzuć procedurę"
                            onclick="return confirm('Porzucić procedurę? Zadanie zostanie anulowane.')"
                        >
                            <i class="bi bi-x-circle me-1"></i>Porzuć
                        </x-ui.button>
                    </form>
                @endif
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if(session('success'))
        <x-ui.alert variant="success" title="Sukces" dismissible class="mb-4">
            {{ session('success') }}
        </x-ui.alert>
    @endif

    @if($isProcedure)
        @include('tasks.partials.show-procedure')
    @elseif($isCallback)
        @include('tasks.partials.show-callback')
    @else
        @include('tasks.partials.show-task')
    @endif
</x-app-layout>
