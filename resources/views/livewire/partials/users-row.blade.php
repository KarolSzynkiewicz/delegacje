@props(['user'])

<tr wire:key="user-{{ $user->id }}">
    <td class="fw-medium">{{ $user->name }}</td>
    <td class="text-muted small">{{ $user->email }}</td>
    <td>
        @if($user->roles->count() > 0)
            <div class="d-flex flex-wrap gap-1">
                @foreach($user->roles as $role)
                    <x-ui.badge variant="primary">{{ $role->name }}</x-ui.badge>
                @endforeach
            </div>
        @else
            <span class="text-muted small">Brak ról</span>
        @endif
    </td>
    <td>
        @if($user->managedProjects->count() > 0)
            <div class="d-flex flex-column gap-1">
                @foreach($user->managedProjects as $project)
                    <x-ui.badge variant="info">{{ $project->name }}</x-ui.badge>
                @endforeach
            </div>
        @else
            <span class="text-muted small">Brak</span>
        @endif
    </td>
    <td class="text-end">
        @if($user->id !== auth()->id())
            <x-action-buttons
                viewRoute="{{ route('users.show', $user) }}"
                editRoute="{{ route('users.edit', $user) }}"
                deleteRoute="{{ route('users.destroy', $user) }}"
                deleteMessage="Czy na pewno chcesz usunąć tego użytkownika?"
            />
        @else
            <x-action-buttons
                viewRoute="{{ route('users.show', $user) }}"
                editRoute="{{ route('users.edit', $user) }}"
            />
        @endif
    </td>
</tr>
