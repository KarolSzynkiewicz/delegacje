<div class="d-flex justify-content-between align-items-center">
    <h2 class="fw-semibold fs-4 text-dark mb-0">{{ $title }}</h2>
    
    <div class="d-flex gap-2">
        {{-- Secondary action slot (named slot dla złożonych przypadków) --}}
        @isset($secondaryAction)
            {{ $secondaryAction }}
        @elseif($secondaryActionLabel)
            <x-ui.button 
                variant="{{ $secondaryActionVariant }}"
                href="{{ $secondaryActionHref }}"
                action="{{ $secondaryActionAction?->value }}"
            >
                {{ $secondaryActionLabel }}
            </x-ui.button>
        @endif
        
        {{-- Primary action slot (named slot dla złożonych przypadków) --}}
        @isset($primaryAction)
            {{ $primaryAction }}
        @elseif($primaryActionLabel)
            <x-ui.button 
                variant="{{ $primaryActionVariant }}"
                href="{{ $primaryActionHref }}"
                action="{{ $primaryActionAction?->value }}"
            >
                {{ $primaryActionLabel }}
            </x-ui.button>
        @endif
        
        {{-- Default slot dla dodatkowych akcji --}}
        {{ $slot }}
    </div>
</div>
