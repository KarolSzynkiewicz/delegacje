<div class="d-flex justify-content-between align-items-center">
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
