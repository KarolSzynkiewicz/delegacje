@php
    $where = $task->meetingLocation();
    $href = $task->meetingLocationHref();
@endphp
@if($where !== '')
    <div @class(['meeting-where', $class ?? 'mb-3'])>
        <i class="bi bi-geo-alt me-1"></i>
        @if($href)
            <a href="{{ $href }}" target="_blank" rel="noopener noreferrer">{{ $where }}</a>
        @else
            {{ $where }}
        @endif
    </div>
@endif
