@props([
    'user',
    'showEmail' => true,
    'avatarSize' => '40px',
    'avatarShape' => 'circle',
    'link' => false,
    'nameClass' => 'fw-semibold',
    'emailClass' => 'small text-muted',
])

@php
    // Generuj inicjały z imienia
    $nameParts = explode(' ', $user->name ?? '');
    $initials = count($nameParts) >= 2 
        ? strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1))
        : strtoupper(substr($user->name ?? '', 0, 1));
    
    // Pobierz URL zdjęcia
    $imageUrl = null;
    if (isset($user->image_path) && $user->image_path) {
        $imageUrl = isset($user->image_url) ? $user->image_url : (\Illuminate\Support\Facades\Storage::disk('public')->exists($user->image_path) ? \Illuminate\Support\Facades\Storage::disk('public')->url($user->image_path) : null);
    }
@endphp

<div class="d-flex align-items-center gap-2">
    <x-ui.avatar 
        :image-url="$imageUrl"
        :alt="$user->name ?? ''"
        :initials="$initials"
        :size="$avatarSize"
        :shape="$avatarShape"
    />
    <div class="flex-grow-1 min-w-0">
        <div class="{{ $nameClass }} text-truncate">
            @if($link && isset($user->id))
                <a href="{{ route('users.show', $user) }}" class="text-decoration-none">
                    {{ $user->name }}
                </a>
            @else
                {{ $user->name ?? '-' }}
            @endif
        </div>
        @if($showEmail && isset($user->email))
            <div class="{{ $emailClass }} text-truncate mt-1">
                <i class="bi bi-envelope me-1"></i>{{ $user->email }}
            </div>
        @endif
    </div>
</div>
