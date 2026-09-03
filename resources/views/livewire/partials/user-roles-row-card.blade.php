@props(['userRole', 'permissionCount' => 0])

<x-ui.card class="dt-card" wire:key="user-role-card-{{ $userRole->id }}">
    <div class="dt-card__title">
        <a href="{{ route('user-roles.show', $userRole) }}" class="stretched-link">{{ $userRole->name }}</a>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Uprawnienia</span>
        <span class="dt-card__value">
            @if($userRole->name === 'administrator')
                <x-ui.badge variant="primary" title="Administrator ma wszystkie uprawnienia przez logikę biznesową">
                    Wszystkie ({{ $permissionCount }})
                </x-ui.badge>
            @else
                <x-ui.badge variant="primary">
                    {{ $userRole->permissions->count() }} uprawnień
                </x-ui.badge>
            @endif
        </span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Użytkownicy</span>
        <span class="dt-card__value">
            <x-ui.badge variant="success">
                {{ $userRole->users_count }} użytkowników
            </x-ui.badge>
        </span>
    </div>

    <div class="dt-card__actions">
        <x-action-buttons
            editRoute="{{ route('user-roles.edit', $userRole) }}"
            deleteRoute="{{ route('user-roles.destroy', $userRole) }}"
            deleteMessage="Czy na pewno chcesz usunąć tę rolę?"
        />
    </div>
</x-ui.card>
