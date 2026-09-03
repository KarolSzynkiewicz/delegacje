@props(['userRole', 'permissionCount' => 0])

<tr wire:key="user-role-{{ $userRole->id }}">
    <td class="fw-medium">{{ $userRole->name }}</td>
    <td>
        @if($userRole->name === 'administrator')
            <x-ui.badge variant="primary" title="Administrator ma wszystkie uprawnienia przez logikę biznesową">
                Wszystkie ({{ $permissionCount }})
            </x-ui.badge>
        @else
            <x-ui.badge variant="primary">
                {{ $userRole->permissions->count() }} uprawnień
            </x-ui.badge>
        @endif
    </td>
    <td>
        <x-ui.badge variant="success">
            {{ $userRole->users_count }} użytkowników
        </x-ui.badge>
    </td>
    <td class="text-end">
        <x-action-buttons
            viewRoute="{{ route('user-roles.show', $userRole) }}"
            editRoute="{{ route('user-roles.edit', $userRole) }}"
            deleteRoute="{{ route('user-roles.destroy', $userRole) }}"
            deleteMessage="Czy na pewno chcesz usunąć tę rolę?"
        />
    </td>
</tr>
