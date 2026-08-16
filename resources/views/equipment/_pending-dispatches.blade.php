<x-ui.card label="Zlecenia do wydania" class="mb-4">
    @if($pendingDispatches->isEmpty())
        <p class="text-muted mb-0">Nic nie czeka na wydanie w tym magazynie.</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>ZW</th>
                        <th>Data</th>
                        <th>Dla kogo</th>
                        <th>Zlecił</th>
                        <th class="text-end">Pozycje</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingDispatches as $dispatch)
                        @php
                            $people = $dispatch->issues
                                ->map(fn ($issue) => $issue->employee?->full_name)
                                ->filter()
                                ->unique()
                                ->values();
                        @endphp
                        <tr>
                            <td class="fw-semibold">
                                <a href="{{ route('warehouse-dispatches.show', $dispatch) }}" class="text-decoration-none">
                                    {{ $dispatch->number }}
                                </a>
                            </td>
                            <td style="white-space:nowrap;">{{ $dispatch->issue_date?->format('d.m.Y') }}</td>
                            <td>
                                {{ $people->take(3)->implode(', ') }}
                                @if($people->count() > 3)
                                    <span class="text-muted">+{{ $people->count() - 3 }}</span>
                                @endif
                            </td>
                            <td>{{ $dispatch->creator?->name ?? '—' }}</td>
                            <td class="text-end" style="font-variant-numeric:tabular-nums;">
                                {{ $dispatch->issues->count() }}
                            </td>
                            <td class="text-end">
                                <x-ui.button
                                    variant="primary"
                                    href="{{ route('warehouse-dispatches.show', $dispatch) }}"
                                    class="btn-sm"
                >
                    Kompletuj
                </x-ui.button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-ui.card>
