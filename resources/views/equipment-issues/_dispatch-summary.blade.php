@php
    $number = $summary['number'] ?? null;
    $recipients = $summary['recipients'] ?? [];
    $lineHasStatus = collect($recipients)->contains(
        fn (array $recipient) => collect($recipient['lines'] ?? [])->contains(fn (array $line) => filled($line['status'] ?? null))
    );
@endphp
<div class="row g-3 mb-4">
    @if($number)
        <div class="col-md-4">
            <h6 class="text-muted small mb-1">Numer</h6>
            <p class="fw-semibold mb-0">{{ $number }}</p>
        </div>
    @endif
    <div class="col-md-4">
        <h6 class="text-muted small mb-1">Magazyn</h6>
        <p class="fw-semibold mb-0">{{ $summary['warehouse'] }}</p>
    </div>
    @if(!empty($summary['status']))
        <div class="col-md-4">
            <h6 class="text-muted small mb-1">Status</h6>
            <p class="fw-semibold mb-0">{{ $summary['status'] }}</p>
        </div>
    @endif
    <div class="col-md-4">
        <h6 class="text-muted small mb-1">{{ !empty($summary['is_reserved']) ? 'Data zlecenia' : 'Data wydania' }}</h6>
        <p class="fw-semibold mb-0">{{ $summary['issue_date'] }}</p>
    </div>
    @if(!empty($summary['issuer_name']))
        <div class="col-md-4">
            <h6 class="text-muted small mb-1">Zlecił</h6>
            <p class="fw-semibold mb-0">{{ $summary['issuer_name'] }}</p>
        </div>
    @endif
    @if(empty($summary['is_reserved']) && !empty($summary['fulfilled_by']))
        <div class="col-md-4">
            <h6 class="text-muted small mb-1">Wydał</h6>
            <p class="fw-semibold mb-0">{{ $summary['fulfilled_by'] }}</p>
        </div>
    @endif
    <div class="col-md-4">
        <h6 class="text-muted small mb-1">Osoby / pozycje</h6>
        <p class="fw-semibold mb-0">{{ $summary['people_count'] }} os. · {{ $summary['position_count'] }} poz.</p>
    </div>
    @if(!empty($summary['notes']))
        <div class="col-12">
            <h6 class="text-muted small mb-1">Notatki</h6>
            <p class="mb-0">{{ $summary['notes'] }}</p>
        </div>
    @endif
</div>

<h4 class="fs-6 fw-bold mb-3">Co kto dostaje</h4>
<div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead>
            <tr>
                <th>Pracownik</th>
                <th>Pozycja</th>
                <th>Rodzaj</th>
                <th class="text-end">Ilość</th>
                <th>Typ</th>
                @if(!empty($lineHasStatus))
                    <th>Status</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($recipients as $recipient)
                @foreach($recipient['lines'] as $index => $line)
                    <tr>
                        @if($index === 0)
                            <td rowspan="{{ count($recipient['lines']) }}" class="fw-semibold">{{ $recipient['name'] }}</td>
                        @endif
                        <td>{{ $line['item'] }}</td>
                        <td>{{ $line['variant'] }}</td>
                        <td class="text-end" style="font-variant-numeric:tabular-nums;">{{ $line['quantity'] }}</td>
                        <td>
                            <x-ui.badge variant="{{ $line['kind'] === 'Bezzwrotne' ? 'accent' : 'info' }}">
                                {{ $line['kind'] }}
                            </x-ui.badge>
                        </td>
                        @if($lineHasStatus)
                            <td>
                                @if(!empty($line['status']))
                                    <x-ui.badge variant="{{ $line['status_variant'] ?? 'info' }}">{{ $line['status'] }}</x-ui.badge>
                                @endif
                            </td>
                        @endif
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="{{ $lineHasStatus ? 6 : 5 }}" class="text-muted">Brak pozycji.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
