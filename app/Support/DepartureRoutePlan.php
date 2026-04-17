<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Normalizacja i zapis planu trasy wyjazdu wielosegmentowego (transport publiczny).
 *
 * @phpstan-type Segment array{
 *   id?: string,
 *   mode: 'public'|'own',
 *   hub_kind?: string|null,
 *   start_location_id?: int|string|null,
 *   end_location_id?: int|string|null,
 *   ticket_costs_by_employee?: array<int|string, array<string, mixed>>,
 *   transfer_config?: array<string, mixed>|null,
 * }
 */
final class DepartureRoutePlan
{
    /**
     * Domyślny układ: lot + transfer z lotniska (zgodny ze starym kreatorem).
     *
     * @param  array<int|string, array<string, mixed>>  $ticketCostsByEmployee
     * @param  array<string, mixed>  $transferConfig
     * @param  array<int, string>  $routeWaypoints
     * @param  array<string, string>  $locationStopNotes
     * @return list<array<string, mixed>>
     */
    public static function defaultTwoSegmentPlan(
        ?string $hubKind,
        $startLocationId,
        $endLocationId,
        array $ticketCostsByEmployee,
        array $transferConfig,
        array $routeWaypoints,
        array $locationStopNotes = [],
    ): array {
        return [
            [
                'id' => (string) Str::uuid(),
                'mode' => 'public',
                'hub_kind' => $hubKind,
                'start_location_id' => $startLocationId !== null && $startLocationId !== '' ? (int) $startLocationId : null,
                'end_location_id' => $endLocationId !== null && $endLocationId !== '' ? (int) $endLocationId : null,
                'ticket_costs_by_employee' => $ticketCostsByEmployee,
            ],
            [
                'id' => (string) Str::uuid(),
                'mode' => 'own',
                'leg' => 'from_airport',
                'ground_mode' => 'car',
                'transfer_config' => $transferConfig,
                'route_waypoints' => array_values($routeWaypoints),
                'location_stop_notes' => $locationStopNotes,
            ],
        ];
    }

    /**
     * Segment „własny” przed lotem: dojazd firmą na lotnisko startowe (opcjonalny).
     *
     * @return array<string, mixed>
     */
    public static function makeToAirportOwnSegment(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'mode' => 'own',
            'leg' => 'to_airport',
            'ground_mode' => 'car',
            'route_waypoints' => [],
            'location_stop_notes' => [],
            'transfer_config' => [],
            'route_metrics' => null,
        ];
    }

    /**
     * Buduje pozycje kosztów biletów — po jednej na (pracownik × segment publiczny).
     *
     * Oczekuje już przetworzonych załączników (attachment_path) w ticket_costs_by_employee.
     *
     * @param  list<array<string, mixed>>  $segments
     * @param  iterable<int|string>  $employeeIds
     * @return list<array{
     *   employee_id: int,
     *   segment_index: int,
     *   start_airport_location_id: int,
     *   end_airport_location_id: int,
     *   amount: float,
     *   currency: string,
     *   attachment_path: ?string,
     * }>
     */
    public static function buildTicketLineItems(array $segments, iterable $employeeIds): array
    {
        $lines = [];
        $empIds = is_array($employeeIds) ? $employeeIds : iterator_to_array($employeeIds);

        foreach ($segments as $segIndex => $seg) {
            if (($seg['mode'] ?? '') !== 'public') {
                continue;
            }
            $start = (int) ($seg['start_location_id'] ?? 0);
            $end = (int) ($seg['end_location_id'] ?? 0);
            $tickets = $seg['ticket_costs_by_employee'] ?? [];
            foreach ($empIds as $eid) {
                $eid = (int) $eid;
                $cost = $tickets[$eid] ?? $tickets[(string) $eid] ?? [];
                $amount = $cost['amount'] ?? null;
                if ($amount === null || $amount === '' || ! is_numeric($amount) || (float) $amount <= 0) {
                    continue;
                }
                $currency = strtoupper(trim((string) ($cost['currency'] ?? 'PLN')));
                $attachmentPath = ! empty($cost['attachment_path']) ? (string) $cost['attachment_path'] : null;
                $lines[] = [
                    'employee_id' => $eid,
                    'segment_index' => (int) $segIndex,
                    'start_airport_location_id' => $start,
                    'end_airport_location_id' => $end,
                    'amount' => (float) $amount,
                    'currency' => $currency,
                    'attachment_path' => $attachmentPath,
                ];
            }
        }

        return $lines;
    }

    /**
     * @param  list<array<string, mixed>>  $segments
     * @return list<array<string, mixed>>
     */
    public static function ownSegmentsInOrder(array $segments): array
    {
        $out = [];
        foreach ($segments as $seg) {
            if (($seg['mode'] ?? '') === 'own') {
                $out[] = $seg;
            }
        }

        return $out;
    }

    /**
     * Łączy waypointy wszystkich segmentów „własnych” w kolejności planu (np. do ustalenia lokalizacji docelowej).
     *
     * @param  list<array<string, mixed>>  $segments
     * @return list<string>
     */
    public static function mergeOwnSegmentWaypoints(array $segments): array
    {
        $merged = [];
        foreach ($segments as $seg) {
            if (($seg['mode'] ?? '') !== 'own') {
                continue;
            }
            $wps = $seg['route_waypoints'] ?? [];
            if (! is_array($wps)) {
                continue;
            }
            foreach ($wps as $w) {
                if ($w !== null && $w !== '') {
                    $merged[] = (string) $w;
                }
            }
        }

        return $merged;
    }

    /**
     * Segment „własny” opisujący transfer po przylocie (domy, km) — nie mylić z odcinkiem „na lotnisko”.
     *
     * @param  list<array<string, mixed>>  $segments
     * @return array<string, mixed>|null
     */
    public static function primaryPostAirportOwnSegment(array $segments): ?array
    {
        foreach (array_reverse($segments) as $seg) {
            if (($seg['mode'] ?? '') !== 'own') {
                continue;
            }
            $leg = $seg['leg'] ?? '';
            if ($leg === 'from_airport' || $leg === '') {
                return $seg;
            }
        }

        return null;
    }

    /**
     * Indeks segmentu own dla transferu po locie (do aktualizacji transfer_config / waypointów).
     *
     * @param  list<array<string, mixed>>  $segments
     */
    public static function primaryPostAirportOwnSegmentIndex(array $segments): ?int
    {
        foreach (array_reverse(array_keys($segments), true) as $i) {
            $seg = $segments[$i];
            if (($seg['mode'] ?? '') !== 'own') {
                continue;
            }
            $leg = $seg['leg'] ?? '';
            if ($leg === 'from_airport' || $leg === '') {
                return (int) $i;
            }
        }

        return null;
    }
}
