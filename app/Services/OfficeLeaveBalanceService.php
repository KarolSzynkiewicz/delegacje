<?php

namespace App\Services;

use App\Enums\OfficeWorkLocation;
use App\Models\OfficeLeaveAdjustment;
use App\Models\OfficeProfile;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class OfficeLeaveBalanceService
{
    /**
     * Saldo urlopu za rok: wymiar + korekty − wykorzystane (dni Leave w siatce).
     *
     * @return array{
     *     year: int,
     *     entitlement: int,
     *     adjustments: int,
     *     available: int,
     *     used: int,
     *     remaining: int,
     *     overused: bool,
     *     days: list<string>,
     *     ranges: list<array{start: string, end: string, days: int, label: string}>,
     *     adjustment_rows: list<array{id: int, days: int, signed: string, reason: string, effective_on: ?string, created_at: ?string, creator: ?string}>
     * }
     */
    public function forProfile(OfficeProfile $profile, ?int $year = null): array
    {
        $year = $year ?? (int) now()->year;
        $entitlement = max(0, (int) ($profile->annual_leave_days ?? 26));

        $adjustmentRows = $profile->leaveAdjustments()
            ->with('creator:id,name')
            ->where('year', $year)
            ->orderByDesc('id')
            ->get();

        $adjustments = (int) $adjustmentRows->sum('days');
        $available = $entitlement + $adjustments;

        $days = $profile->workDays()
            ->where('location', OfficeWorkLocation::Leave->value)
            ->whereYear('work_date', $year)
            ->orderBy('work_date')
            ->pluck('work_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->values();

        $used = $days->count();
        $remaining = $available - $used;

        return [
            'year' => $year,
            'entitlement' => $entitlement,
            'adjustments' => $adjustments,
            'available' => $available,
            'used' => $used,
            'remaining' => $remaining,
            'overused' => $remaining < 0,
            'days' => $days->all(),
            'ranges' => $this->groupIntoRanges($days),
            'adjustment_rows' => $adjustmentRows->map(fn (OfficeLeaveAdjustment $row) => [
                'id' => $row->id,
                'days' => (int) $row->days,
                'signed' => $row->signedLabel(),
                'reason' => $row->reason,
                'effective_on' => $row->effective_on?->toDateString(),
                'created_at' => $row->created_at?->format('Y-m-d H:i'),
                'creator' => $row->creator?->name,
            ])->all(),
        ];
    }

    /**
     * @param  Collection<int, string>  $days  Y-m-d
     * @return list<array{start: string, end: string, days: int, label: string}>
     */
    protected function groupIntoRanges(Collection $days): array
    {
        if ($days->isEmpty()) {
            return [];
        }

        $ranges = [];
        $start = Carbon::parse($days->first())->startOfDay();
        $prev = $start->copy();

        foreach ($days->slice(1) as $iso) {
            $current = Carbon::parse($iso)->startOfDay();
            if ((int) round($current->diffInDays($prev)) === 1) {
                $prev = $current;
                continue;
            }

            $ranges[] = $this->rangePayload($start, $prev);
            $start = $current;
            $prev = $current;
        }

        $ranges[] = $this->rangePayload($start, $prev);

        return $ranges;
    }

    /**
     * @return array{start: string, end: string, days: int, label: string}
     */
    protected function rangePayload(Carbon $start, Carbon $end): array
    {
        $days = (int) $start->diffInDays($end) + 1;
        $label = $start->equalTo($end)
            ? $start->format('d.m.Y')
            : $start->format('d.m.Y').' – '.$end->format('d.m.Y');

        return [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'days' => $days,
            'label' => $label,
        ];
    }
}
