@php
    $hasLeft = isset($left);
    $hasRight = isset($right);
    $hasSlot = isset($slot) && trim($slot);
    $buttonCount = ($hasLeft ? 1 : 0) + ($hasRight ? 1 : 0) + ($hasSlot ? 1 : 0);
    $isSingleButton = $buttonCount === 1 && $hasRight && !$hasLeft && !$hasSlot;
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

@if($isSingleButton)
    {{-- Desktop: jeden przycisk - kolumna (tytuł na górze, przycisk poniżej) --}}
    <div class="d-none d-md-flex flex-column gap-3">
        <div class="text-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">{{ $title }}</h2>
        </div>
        <div class="d-flex justify-content-center">
            {{ $right }}
        </div>
    </div>
@else
    {{-- Desktop: poziomy layout (jak wcześniej) - gdy są 2+ przyciski lub left --}}
    <div class="d-none d-md-flex justify-content-between align-items-center">
        {{-- Lewa strona - przyciski po lewej (np. Back) --}}
        <div class="d-flex align-items-center gap-2" style="flex: 0 0 auto;">
            @isset($left)
                {{ $left }}
            @else
                {{-- Pusty div dla zachowania równowagi layoutu --}}
                <div style="width: 0;"></div>
            @endif
        </div>
        
        {{-- Środek - tytuł wyśrodkowany --}}
        <div class="text-center" style="flex: 1 1 auto;">
            <h2 class="fw-semibold fs-4 text-dark mb-0">{{ $title }}</h2>
        </div>
        
        {{-- Prawa strona - przyciski po prawej (np. Create, Edit) --}}
        <div class="d-flex gap-2" style="flex: 0 0 auto;">
            @isset($right)
                {{ $right }}
            @endif
            
            {{-- Default slot dla dodatkowych akcji --}}
            {{ $slot }}
        </div>
    </div>
@endif
