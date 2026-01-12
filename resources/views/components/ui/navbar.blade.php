@props([
    'brand' => 'Stocznia PRO',
    'brandUrl' => '#',
])

<nav class="navbar-ui">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center w-100">
            <a href="{{ $brandUrl }}" class="navbar-brand text-white fw-bold text-decoration-none">
                {{ $brand }}
            </a>
            <div class="d-flex align-items-center gap-3">
                {{ $slot }}
            </div>
        </div>
    </div>
</nav>
