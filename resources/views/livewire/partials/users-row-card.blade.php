@props(['user'])

<x-ui.card class="dt-card" wire:key="user-card-{{ $user->id }}">
    <div class="dt-card__title">
        <a href="{{ route('users.show', $user) }}" class="stretched-link">{{ $user->name }}</a>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Email</span>
        <span class="dt-card__value">{{ $user->email }}</span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Role</span>
        <span class="dt-card__value">
            @if($user->roles->count() > 0)
                @foreach($user->roles as $role)
                    <x-ui.badge variant="primary">{{ $role->name }}</x-ui.badge>
                @endforeach
            @else
                <span class="text-muted">Brak ról</span>
            @endif
        </span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Kierownik</span>
        <span class="dt-card__value">
            @if($user->managedProjects->count() > 0)
                @foreach($user->managedProjects as $project)
                    <x-ui.badge variant="info">{{ $project->name }}</x-ui.badge>
                @endforeach
            @else
                <span class="text-muted">Brak</span>
            @endif
        </span>
    </div>

    <div class="dt-card__actions">
        @if($user->id !== auth()->id())
            <x-action-buttons
                editRoute="{{ route('users.edit', $user) }}"
                deleteRoute="{{ route('users.destroy', $user) }}"
                deleteMessage="Czy na pewno chcesz usunąć tego użytkownika?"
            />
        @else
            <x-action-buttons
                editRoute="{{ route('users.edit', $user) }}"
            />
        @endif
    </div>
</x-ui.card>
