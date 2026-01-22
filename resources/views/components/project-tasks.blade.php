@props(['project', 'users'])

<x-ui.card label="Zadania projektu">
    <form action="{{ route('projects.tasks.store', $project) }}" method="POST" class="mb-4">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <x-ui.input 
                    type="text" 
                    name="name" 
                    label="Nazwa zadania"
                    required
                />
            </div>
            <div class="col-md-6">
                <x-ui.input 
                    type="select" 
                    name="assigned_to" 
                    label="Przypisz do"
                >
                    <option value="">Brak przypisania</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </x-ui.input>
            </div>
            <div class="col-md-12">
                <x-ui.input 
                    type="textarea" 
                    name="description" 
                    label="Opis"
                    rows="3"
                />
            </div>
            <div class="col-md-6">
                <x-ui.input 
                    type="date" 
                    name="due_date" 
                    label="Termin wykonania"
                />
            </div>
            <div class="col-md-6">
                <x-ui.input 
                    type="select" 
                    name="status" 
                    label="Status"
                >
                    <option value="pending" {{ old('status', 'pending') === 'pending' ? 'selected' : '' }}>Oczekujące</option>
                    <option value="in_progress" {{ old('status') === 'in_progress' ? 'selected' : '' }}>W trakcie</option>
                    <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Zakończone</option>
                    <option value="cancelled" {{ old('status') === 'cancelled' ? 'selected' : '' }}>Anulowane</option>
                </x-ui.input>
            </div>
        </div>
        <div class="mt-3">
            <x-ui.button variant="primary" type="submit" action="save">
                Dodaj zadanie
            </x-ui.button>
        </div>
    </form>

    @php
        $tasks = $project->tasks;
        $pending = $tasks->filter(fn($task) => $task->status->value === \App\Enums\TaskStatus::PENDING->value);
        $inProgress = $tasks->filter(fn($task) => $task->status->value === \App\Enums\TaskStatus::IN_PROGRESS->value);
        $completed = $tasks->filter(fn($task) => $task->status->value === \App\Enums\TaskStatus::COMPLETED->value);
        $cancelled = $tasks->filter(fn($task) => $task->status->value === \App\Enums\TaskStatus::CANCELLED->value);
    @endphp

    @if($tasks->count() > 0)
        <div class="mb-3">
            <ul class="nav nav-pills" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#all-tasks">Wszystkie ({{ $tasks->count() }})</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pending-tasks">Oczekujące ({{ $pending->count() }})</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#in-progress-tasks">W trakcie ({{ $inProgress->count() }})</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#completed-tasks">Zakończone ({{ $completed->count() }})</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#cancelled-tasks">Anulowane ({{ $cancelled->count() }})</button>
                </li>
            </ul>
        </div>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="all-tasks">
                @include('components.project-tasks-list', ['tasks' => $tasks, 'project' => $project, 'users' => $users])
            </div>
            <div class="tab-pane fade" id="pending-tasks">
                @include('components.project-tasks-list', ['tasks' => $pending, 'project' => $project, 'users' => $users])
            </div>
            <div class="tab-pane fade" id="in-progress-tasks">
                @include('components.project-tasks-list', ['tasks' => $inProgress, 'project' => $project, 'users' => $users])
            </div>
            <div class="tab-pane fade" id="completed-tasks">
                @include('components.project-tasks-list', ['tasks' => $completed, 'project' => $project, 'users' => $users])
            </div>
            <div class="tab-pane fade" id="cancelled-tasks">
                @include('components.project-tasks-list', ['tasks' => $cancelled, 'project' => $project, 'users' => $users])
            </div>
        </div>
    @else
        <x-ui.empty-state 
            icon="list-check"
            message="Brak zadań"
        />
    @endif
</x-ui.card>

@push('scripts')
<script>
    // Simple tab switching without Bootstrap JS dependency
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-bs-target');
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            this.classList.add('active');
            document.querySelector(targetId)?.classList.add('show', 'active');
        });
    });
</script>
@endpush
