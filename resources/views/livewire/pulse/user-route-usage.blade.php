<x-ui.card>
    <x-ui.table-header
        title="Sposób użycia"
        subtitle="Pogrupowane po ścieżce, ostatnie {{ $periodLabel }}"
        class="mb-3"
    >
        <x-slot:actions>
            <div class="btn-group btn-group-sm" role="group" aria-label="Okres">
                @foreach (\App\Livewire\Pulse\UserRouteUsage::PERIODS as $value => $label)
                    <button
                        type="button"
                        wire:click="setPeriod({{ \Illuminate\Support\Js::from($value) }})"
                        class="btn {{ $this->period === $value ? 'btn-primary' : 'btn-outline-secondary' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </x-slot:actions>
    </x-ui.table-header>

    @if ($matrixRows->isEmpty() || $usageUser === null)
        <p class="text-muted small mb-0">Brak wejść w wybranym okresie</p>
    @else
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Route</th>
                        <th class="text-end" title="{{ $usageUser->name }}{{ $usageUser->extra ? ' · '.$usageUser->extra : '' }}">
                            {{ \Illuminate\Support\Str::limit($usageUser->name, 16) }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($matrixRows as $row)
                        @php $value = $row->cells[$usageUser->id] ?? 0; @endphp
                        <tr wire:key="{{ $row->key }}-row">
                            <td class="{{ $row->has_children ? 'fw-semibold' : '' }}">
                                <div class="d-flex align-items-center gap-2 min-w-0" style="padding-left: {{ $row->depth * 1.05 }}rem">
                                    @if ($row->has_children)
                                        <button
                                            type="button"
                                            wire:click="toggleGroup({{ \Illuminate\Support\Js::from($row->key) }})"
                                            class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center p-0"
                                            style="width: 1.5rem; height: 1.5rem;"
                                            title="{{ $row->expanded ? 'Zwiń '.$row->path : 'Rozwiń '.$row->path }}"
                                            aria-expanded="{{ $row->expanded ? 'true' : 'false' }}"
                                        >
                                            <span class="lh-1">{{ $row->expanded ? '▾' : '▸' }}</span>
                                        </button>
                                    @else
                                        <span class="d-inline-block" style="width: 1.5rem;"></span>
                                    @endif
                                    <span class="min-w-0 text-truncate small" title="{{ $row->path }}">
                                        @if ($row->is_self)
                                            <span class="fst-italic text-muted">{{ $row->label }}</span>
                                        @elseif ($row->depth === 0)
                                            <code class="text-reset">/{{ $row->label }}</code>
                                        @else
                                            <code>
                                                <span class="text-muted">{{ $row->parent_path }}/</span><span class="text-reset">{{ $row->label }}</span>
                                            </code>
                                        @endif
                                    </span>
                                </div>
                            </td>
                            <td class="text-end font-monospace {{ $value > 0 ? 'fw-semibold' : 'text-muted' }}">
                                {{ number_format($value) }}
                            </td>
                        </tr>
                    @endforeach
                    <tr wire:key="matrix-total-row">
                        <td class="fw-semibold">Total</td>
                        <td class="text-end font-monospace fw-bold">
                            {{ number_format($columnTotals[$usageUser->id] ?? 0) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif
</x-ui.card>
