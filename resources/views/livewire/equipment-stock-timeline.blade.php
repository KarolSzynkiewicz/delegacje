<div>
    <h5 class="mb-1">Historia ruchów magazynowych</h5>
    <p class="text-muted small mb-3">
        Każdy ruch — przyjęcie, wydanie, zwrot, przemieszczenie, korekta, rozchód — z ilością i osobą, która go wykonała.
    </p>
    @if($entries->count() > 0)
        <div>
            @foreach($entries as $index => $entry)
                @php
                    $qtyColor = $entry['signed_quantity'] > 0
                        ? '#14b8a6'
                        : ($entry['signed_quantity'] < 0 ? '#f43f5e' : 'var(--text-main)');
                @endphp
                <div
                    wire:key="timeline-{{ $entries->firstItem() + $index }}"
                    class="d-flex align-items-start gap-3 py-3"
                    style="border-bottom:1px solid var(--glass-border);"
                >
                    <span
                        style="width:10px;height:10px;border-radius:50%;background:{{ $entry['dot_color'] }};margin-top:.45rem;flex-shrink:0;box-shadow:0 0 8px {{ $entry['dot_color'] }};"
                    ></span>
                    <div class="flex-grow-1" style="min-width:0;">
                        <div class="fw-semibold">
                            @if(! empty($entry['href']))
                                <a href="{{ $entry['href'] }}" class="text-decoration-none">{{ $entry['title'] }}</a>
                            @else
                                {{ $entry['title'] }}
                            @endif
                        </div>
                        <div class="small text-muted">{{ $entry['meta'] }}</div>
                        @if(! empty($entry['lines']) && count($entry['lines']) > 1)
                            <ul class="list-unstyled small text-muted mb-0 mt-1">
                                @foreach($entry['lines'] as $line)
                                    <li>
                                        @if(! empty($line['employee']) && ! empty($line['sku']))
                                            {{ $line['employee'] }} · {{ $line['sku'] }} · {{ $line['quantity'] }} szt.
                                        @elseif(! empty($line['sku']))
                                            {{ $line['sku'] }} · {{ $line['quantity'] }} szt.
                                        @else
                                            {{ $line['employee'] }} · {{ $line['quantity'] }} szt.
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @elseif(! empty($entry['lines']) && count($entry['lines']) === 1)
                            <div class="small text-muted">{{ $entry['lines'][0]['employee'] }} · {{ $entry['lines'][0]['sku'] }}</div>
                        @endif
                        @if($entry['notes'])
                            <div class="small text-muted mt-1">{{ $entry['notes'] }}</div>
                        @endif
                    </div>
                    <div
                        class="fw-semibold text-nowrap"
                        style="color:{{ $qtyColor }};font-variant-numeric:tabular-nums;"
                    >
                        {{ $entry['quantity_label'] }}
                    </div>
                </div>
            @endforeach
        </div>
        @if($entries->hasPages())
            <div class="mt-3">
                {{ $entries->links(data: ['scrollTo' => false]) }}
            </div>
        @endif
    @else
        <p class="text-muted mb-0">Brak ruchów. Przyjmij, wydaj, przemieść albo zrób korektę, żeby pojawił się ślad.</p>
    @endif
</div>
