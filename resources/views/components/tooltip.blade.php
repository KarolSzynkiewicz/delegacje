@props([
    'title' => 'Wskazówka',
    'direction' => 'top', // top, bottom
])

@php
    $tooltipId = 'tooltip-' . uniqid();
    $isBottom = $direction === 'bottom';
@endphp

<style>
    .tooltip-hotspot {
        position: relative;
        display: inline-flex;
        align-items: center;
        cursor: help;
    }

    .tooltip-box {
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        min-width: 280px;
        background: rgba(20, 20, 20, 0.95);
        border-radius: 14px;
        padding: 14px 16px;
        box-shadow: 0 16px 45px rgba(0, 0, 0, .5);
        font-size: 13px;
        line-height: 1.45;
        opacity: 0;
        pointer-events: none;
        transition: opacity .15s ease, transform .15s ease;
        z-index: 9999;
    }

    .tooltip-box.tooltip-top {
        bottom: 140%;
    }

    .tooltip-box.tooltip-bottom {
        top: 140%;
    }

    .tooltip-hotspot.active .tooltip-box.tooltip-top {
        opacity: 1;
        pointer-events: auto;
        transform: translateX(-50%) translateY(-4px);
    }

    .tooltip-hotspot.active .tooltip-box.tooltip-bottom {
        opacity: 1;
        pointer-events: auto;
        transform: translateX(-50%) translateY(4px);
    }

    .tooltip-title {
        font-weight: 600;
        margin-bottom: 4px;
        color: #ffffff;
    }

    .tooltip-text {
        color: #ffffff;
    }
</style>

<span class="tooltip-hotspot" id="{{ $tooltipId }}" onclick="event.stopPropagation()">
    {{ $slot }}
    
    <span class="tooltip-box {{ $isBottom ? 'tooltip-bottom' : 'tooltip-top' }}">
        <span style="display: flex; gap: 10px; align-items: flex-start;">
            <i class="bi bi-lightbulb-fill" style="font-size: 18px; color: #facc15;"></i>
            <span>
                <span class="tooltip-title">{{ $title }}</span>
            </span>
        </span>
    </span>
</span>

<script>
    (function() {
        const tooltipElement = document.getElementById('{{ $tooltipId }}');
        if (!tooltipElement) return;

        // Toggle tooltip on click
        tooltipElement.addEventListener('click', function(e) {
            e.stopPropagation();
            tooltipElement.classList.toggle('active');
        });

        // Close tooltip when clicking outside
        document.addEventListener('click', function(e) {
            if (!tooltipElement.contains(e.target)) {
                tooltipElement.classList.remove('active');
            }
        });

        // Close tooltip on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                tooltipElement.classList.remove('active');
            }
        });
    })();
</script>
