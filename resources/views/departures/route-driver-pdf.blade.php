<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Instrukcja dla kierowcy — wyjazd #{{ $departure->id }}</title>
    <style>
        @page { margin: 28px 32px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111;
            line-height: 1.45;
        }
        h1 { font-size: 16px; margin: 0 0 4px; }
        h2 { font-size: 12px; margin: 18px 0 8px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        .muted { color: #555; }
        .meta { margin-bottom: 14px; }
        .meta table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 3px 6px 3px 0; vertical-align: top; }
        .meta .label { color: #555; width: 28%; }
        .stop {
            border: 1px solid #bbb;
            border-radius: 4px;
            padding: 8px 10px;
            margin-bottom: 8px;
            page-break-inside: avoid;
        }
        .stop-head {
            font-weight: bold;
            margin-bottom: 2px;
        }
        .badge {
            display: inline-block;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 1px 6px;
            border: 1px solid #999;
            border-radius: 10px;
            margin-right: 6px;
            color: #333;
        }
        .note {
            margin-top: 6px;
            padding: 6px 8px;
            background: #f3f3f3;
            border-left: 3px solid #444;
        }
        .note-label { font-size: 9px; color: #555; margin-bottom: 2px; text-transform: uppercase; letter-spacing: 0.03em; }
        ul.people { margin: 4px 0 0 16px; padding: 0; }
        .footer { margin-top: 22px; font-size: 9px; color: #666; border-top: 1px solid #ddd; padding-top: 6px; }
    </style>
</head>
<body>
    <h1>Instrukcja dla kierowcy</h1>
    <div class="muted">Rozpiska trasy — wyjazd #{{ $departure->id }}</div>

    <div class="meta">
        <table>
            <tr>
                <td class="label">Data wyjazdu</td>
                <td><strong>{{ $departure->event_date?->format('d.m.Y H:i') ?? '—' }}</strong></td>
            </tr>
            <tr>
                <td class="label">Data przybycia</td>
                <td><strong>{{ $departure->end_date?->format('d.m.Y H:i') ?? '—' }}</strong></td>
            </tr>
            <tr>
                <td class="label">Pojazd</td>
                <td>
                    @if($departure->vehicle)
                        <strong>{{ $departure->vehicle->registration_number }}</strong>
                        — {{ $departure->vehicle->brand }} {{ $departure->vehicle->model }}
                    @else
                        —
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">Kierowca</td>
                <td><strong>{{ $driverName ?? '—' }}</strong></td>
            </tr>
            @if($departure->hasRouteData())
                <tr>
                    <td class="label">Szacowany dystans</td>
                    <td>{{ $departure->getFormattedDistance() ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Szacowany czas jazdy</td>
                    <td>{{ $departure->getFormattedDuration() ?? '—' }}</td>
                </tr>
            @endif
        </table>
    </div>

    <h2>Uczestnicy</h2>
    @if($participants->isEmpty())
        <p class="muted">Brak uczestników.</p>
    @else
        <ul class="people">
            @foreach($participants as $p)
                <li>{{ $p }}</li>
            @endforeach
        </ul>
    @endif

    <h2>Przebieg trasy</h2>

    {{-- Start --}}
    <div class="stop">
        <div class="stop-head">
            <span class="badge">Start</span>
            {{ $departure->fromLocation?->name ?? '—' }}
        </div>
        @if($departure->fromLocation)
            <div class="muted">
                {{ trim(implode(', ', array_filter([$departure->fromLocation->address, $departure->fromLocation->city]))) }}
            </div>
        @endif
    </div>

    {{-- Przystanki --}}
    @foreach($routeStops as $stop)
        <div class="stop">
            <div class="stop-head">
                <span class="badge">{{ $stop['kind'] === 'accommodation' ? 'Mieszkanie' : 'Przystanek' }} {{ $stop['position'] }}</span>
                {{ $stop['name'] }}
            </div>
            @if(($stop['address_line'] ?? '') !== '')
                <div class="muted">{{ $stop['address_line'] }}</div>
            @endif
            @if(!empty($stop['employees_label']))
                <div style="margin-top: 4px;"><strong>Dla:</strong> {{ $stop['employees_label'] }}</div>
            @endif
            @if(!empty($stop['purpose']))
                <div class="note">
                    <div class="note-label">Po co tu jedziemy</div>
                    <div style="white-space: pre-wrap;">{{ $stop['purpose'] }}</div>
                </div>
            @endif
        </div>
    @endforeach

    {{-- Cel --}}
    <div class="stop">
        <div class="stop-head">
            <span class="badge">Cel</span>
            {{ $departure->toLocation?->name ?? '—' }}
        </div>
        @if($departure->toLocation)
            <div class="muted">
                {{ trim(implode(', ', array_filter([$departure->toLocation->address, $departure->toLocation->city]))) }}
            </div>
        @endif
    </div>

    @if(!empty($departure->notes))
        <h2>Notatki do wyjazdu</h2>
        <div class="note" style="white-space: pre-wrap;">{{ $departure->notes }}</div>
    @endif

    <div class="footer">
        Wygenerowano {{ now()->format('d.m.Y H:i') }} · system delegacji · wyjazd #{{ $departure->id }}
    </div>
</body>
</html>
