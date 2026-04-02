@php
    $hasSlot = isset($slot) && trim($slot);
@endphp

{{-- Mobile: kolumna (tytuł na górze, przyciski poniżej) --}}
<div class="d-flex flex-column d-md-none gap-3">
    {{-- Tytuł na górze --}}
    <div class="text-center">
        <h2 class="fw-semibold fs-4 text-dark mb-0">{{ $title }}</h2>
    </div>
    
    {{-- Przyciski obok siebie na pełnej szerokości --}}
    <div class="d-flex gap-2 w-100">
        @isset($left)
            <div class="flex-fill">
                {{ $left }}
            </div>
        @endif
        
        @isset($right)
            <div class="flex-fill">
                {{ $right }}
            </div>
        @endif
        
        {{-- Default slot dla dodatkowych akcji --}}
        @if(isset($slot) && trim($slot))
            <div class="flex-fill">
                {{ $slot }}
            </div>
        @endif
    </div>
</div>

{{-- Desktop: tytuł na środku, akcje po prawej (także przy jednym przycisku „Dodaj”) --}}
<div class="d-none d-md-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-2" style="flex: 0 0 auto;">
        @isset($left)
            {{ $left }}
        @else
            <div style="width: 0;"></div>
        @endif
    </div>

    <div class="text-center" style="flex: 1 1 auto;">
        <h2 class="fw-semibold fs-4 text-dark mb-0">{{ $title }}</h2>
    </div>

    <div class="d-flex gap-2" style="flex: 0 0 auto;">
        @isset($right)
            {{ $right }}
        @endif
        @if($hasSlot)
            {{ $slot }}
        @endif
    </div>
</div>
