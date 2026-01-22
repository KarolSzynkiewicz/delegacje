@props(['tasks', 'project', 'users'])

@if($tasks->count() > 0)
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nazwa</th>
                    <th>Przypisany do</th>
                    <th>Status</th>
                    <th>Termin</th>
                    <th class="text-end">Akcje</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tasks as $task)
                    <tr>
                        <td>
                            <strong>{{ $task->name }}</strong>
                            @if($task->description)
                                <br><small class="text-muted">{{ Str::limit($task->description, 50) }}</small>
                            @endif
                        </td>
                        <td>
                            @if($task->assignedTo)
                                <div class="d-flex align-items-center gap-2">
                                    @php
                                        $user = $task->assignedTo;
                                        $nameParts = explode(' ', $user->name);
                                        $initials = count($nameParts) >= 2 
                                            ? strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1))
                                            : strtoupper(substr($user->name, 0, 1));
                                        $imageUrl = null;
                                        if (isset($user->image_path) && $user->image_path) {
                                            $imageUrl = isset($user->image_url) ? $user->image_url : (\Illuminate\Support\Facades\Storage::disk('public')->exists($user->image_path) ? \Illuminate\Support\Facades\Storage::disk('public')->url($user->image_path) : null);
                                        }
                                    @endphp
                                    <x-ui.avatar 
                                        :image-url="$imageUrl"
                                        :alt="$user->name"
                                        :initials="$initials"
                                        size="32px"
                                        shape="circle"
                                    />
                                    <span>{{ $user->name }}</span>
                                </div>
                            @else
                                <span class="text-muted">Nie przypisane</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $badgeVariant = match($task->status) {
                                    \App\Enums\TaskStatus::PENDING => 'warning',
                                    \App\Enums\TaskStatus::IN_PROGRESS => 'info',
                                    \App\Enums\TaskStatus::COMPLETED => 'success',
                                    \App\Enums\TaskStatus::CANCELLED => 'danger',
                                };
                            @endphp
                            <x-ui.badge variant="{{ $badgeVariant }}">{{ $task->status->label() }}</x-ui.badge>
                        </td>
                        <td>
                            @if($task->due_date)
                                {{ $task->due_date->format('d.m.Y') }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <x-tasks-actions :task="$task" :project="$project" size="sm" gap="1" />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <x-ui.empty-state 
        icon="list-check"
        message="Brak zadań w tej kategorii"
    />
@endif
