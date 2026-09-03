@props([
    'definition' => ['nodes' => [], 'edges' => []],
    'highlight' => null,
    'tokens' => [],
    'legend' => 'run',
])

@php
    $nodes = array_values($definition['nodes'] ?? []);
    $edges = array_values($definition['edges'] ?? []);
    $activeIds = $highlight['active'] ?? [];
    $doneIds = $highlight['completed'] ?? [];
    $waitingIds = $highlight['waiting'] ?? [];
    $tokens = is_array($tokens) ? $tokens : [];
    $nodeW = 190;
    $nodeH = 88;
    $typeMeta = [
        'start' => ['icon' => '▶', 'color' => '#3ecf8e'],
        'end' => ['icon' => '⏹', 'color' => '#ef5a6f'],
        'task' => ['icon' => '☰', 'color' => '#5b8def'],
        'checklist' => ['icon' => '☑', 'color' => '#3ecf8e'],
        'decision' => ['icon' => '◆', 'color' => '#f0a84e'],
        'wait' => ['icon' => '⏱', 'color' => '#8b96b3'],
        'note' => ['icon' => '✎', 'color' => '#6b7280'],
    ];

    $laid = [];
    foreach ($nodes as $i => $node) {
        $laid[] = array_merge($node, [
            'x' => is_numeric($node['x'] ?? null) ? (float) $node['x'] : 24 + $i * 220,
            'y' => is_numeric($node['y'] ?? null) ? (float) $node['y'] : 36,
        ]);
    }
    $byId = collect($laid)->keyBy(fn ($n) => (string) ($n['id'] ?? ''));
    $isPreview = $highlight === null;

    $nodeState = function (string $id) use ($isPreview, $activeIds, $doneIds, $waitingIds): string {
        if ($isPreview) {
            return 'preview';
        }
        if (in_array($id, $activeIds, true)) {
            return 'active';
        }
        if (in_array($id, $doneIds, true)) {
            return 'done';
        }
        if (in_array($id, $waitingIds, true)) {
            return 'waiting';
        }

        return 'pending';
    };

    $edgeState = function (array $edge) use ($isPreview, $tokens, $nodeState): string {
        if ($isPreview) {
            return 'preview';
        }
        $from = (string) ($edge['from'] ?? '');
        $to = (string) ($edge['to'] ?? '');
        $arrived = array_map('strval', $tokens[$to] ?? []);
        if (in_array($from, $arrived, true)) {
            return 'token';
        }
        $fromState = $nodeState($from);
        $toState = $nodeState($to);
        if ($fromState === 'done' && in_array($toState, ['done', 'active', 'waiting'], true)) {
            return 'done';
        }

        return 'pending';
    };
@endphp

@if($laid !== [])
    <div {{ $attributes->class('pr-flow') }} data-pr-flow>
        @if($legend === 'run')
            <div class="pr-flow-legend small text-muted mb-2 d-flex flex-wrap gap-3">
                <span><i class="pr-flow-dot pr-flow-dot--active"></i> Aktywny</span>
                <span><i class="pr-flow-dot pr-flow-dot--done"></i> Wykonany</span>
                <span><i class="pr-flow-dot pr-flow-dot--waiting"></i> Czeka na zbieg</span>
                <span><i class="pr-flow-dot pr-flow-dot--pending"></i> Do zrobienia</span>
            </div>
        @endif
        <div class="pr-flow-canvas" data-pr-canvas>
            <div class="pr-flow-world" data-pr-world>
                <svg class="pr-flow-edges" aria-hidden="true">
                    @foreach($edges as $edge)
                        @php
                            $from = $byId->get((string) ($edge['from'] ?? ''));
                            $to = $byId->get((string) ($edge['to'] ?? ''));
                        @endphp
                        @continue(! $from || ! $to)
                        @php
                            $x1 = $from['x'] + $nodeW / 2;
                            $y1 = $from['y'] + $nodeH;
                            $x2 = $to['x'] + $nodeW / 2;
                            $y2 = $to['y'];
                            $dy = max(40, abs($y2 - $y1) / 2);
                            $d = "M {$x1} {$y1} C {$x1} ".($y1 + $dy).", {$x2} ".($y2 - $dy).", {$x2} {$y2}";
                            $state = $edgeState($edge);
                            $size = 6;
                        @endphp
                        <g class="pr-flow-edge pr-flow-edge--{{ $state }}">
                            <path d="{{ $d }}" class="pr-flow-edge-line" />
                            <polygon class="pr-flow-edge-arrow" points="{{ $x2 - $size }},{{ $y2 - 9 }} {{ $x2 + $size }},{{ $y2 - 9 }} {{ $x2 }},{{ $y2 - 1 }}" />
                            @if(! empty($edge['label']))
                                @php $mx = ($x1 + $x2) / 2; $my = ($y1 + $y2) / 2; @endphp
                                <rect class="pr-flow-edge-label-bg" rx="8" x="{{ $mx - max(24, mb_strlen((string) $edge['label']) * 6 + 14) / 2 }}" y="{{ $my - 10 }}" width="{{ max(24, mb_strlen((string) $edge['label']) * 6 + 14) }}" height="20" />
                                <text class="pr-flow-edge-label" x="{{ $mx }}" y="{{ $my + 3.5 }}" text-anchor="middle">{{ $edge['label'] }}</text>
                            @endif
                        </g>
                    @endforeach
                </svg>
                @foreach($laid as $node)
                    @php
                        $id = (string) ($node['id'] ?? '');
                        $type = $node['type'] ?? 'task';
                        $meta = $typeMeta[$type] ?? $typeMeta['task'];
                        $state = $nodeState($id);
                        $color = $node['color'] ?? $meta['color'];
                        $icon = $node['icon'] ?? $meta['icon'];
                    @endphp
                    <div class="pr-flow-node pr-flow-node--{{ $state }}"
                         style="left: {{ $node['x'] }}px; top: {{ $node['y'] }}px;"
                         data-node-id="{{ $id }}">
                        <div class="pr-flow-n-top">
                            <span class="pr-flow-n-icon" style="background: {{ $color }}">{{ $icon }}</span>
                            <span class="pr-flow-n-name">{{ $node['name'] ?? $id }}</span>
                        </div>
                        <div class="pr-flow-n-type">{{ \App\Models\ProcedureRun::nodeTypeLabel($type) }}</div>
                        @if($state === 'active')
                            <div class="pr-flow-n-flag">teraz</div>
                        @elseif($state === 'done')
                            <div class="pr-flow-n-flag pr-flow-n-flag--done">zrobione</div>
                        @elseif($state === 'waiting')
                            <div class="pr-flow-n-flag pr-flow-n-flag--waiting">zbieg</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        <div class="pr-flow-hint text-muted">Przeciągnij tło · scroll = zoom</div>
    </div>
@endif
