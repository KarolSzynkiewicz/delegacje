<?php

namespace App\Support\Calendar\Layers;

use App\Enums\OfficeWorkLocation;
use App\Models\OfficeWorkDay;
use App\Support\Calendar\CalendarEvent;
use App\Support\Calendar\CalendarLayer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class OfficeLeaveLayer extends CalendarLayer
{
    public function key(): string
    {
        return 'office_leave';
    }

    public function label(): string
    {
        return 'Urlopy biurowe';
    }

    public function group(): string
    {
        return 'Biuro';
    }

    public function icon(): string
    {
        return 'bi bi-calendar-x';
    }

    public function color(): string
    {
        return '#c084fc';
    }

    public function permission(): ?string
    {
        return 'office-hours.view';
    }

    public function description(): ?string
    {
        return 'Nieobecności osób z profilu biurowego (dni oznaczone jako urlop)';
    }

    public function fetch(CarbonImmutable $from, CarbonImmutable $to): iterable
    {
        $days = OfficeWorkDay::query()
            ->with(['profile.user:id,name'])
            ->where('location', OfficeWorkLocation::Leave->value)
            ->whereDate('work_date', '>=', $from->toDateString())
            ->whereDate('work_date', '<=', $to->toDateString())
            ->orderBy('office_profile_id')
            ->orderBy('work_date')
            ->limit($this->limit())
            ->get();

        $emitted = 0;
        foreach ($days->groupBy('office_profile_id') as $profileId => $profileDays) {
            foreach ($this->consecutiveRanges($profileDays) as $range) {
                if ($emitted >= $this->limit()) {
                    return;
                }

                $profile = $profileDays->first()?->profile;
                $name = $profile?->user?->name ?? ('Profil #'.$profileId);
                $month = $range['start']->format('Y-m');

                yield new CalendarEvent(
                    layer: $this->key(),
                    id: $profileId.'_'.$range['start']->toDateString(),
                    title: $name,
                    start: $range['start'],
                    end: $range['end'],
                    subtitle: 'Urlop · '.$range['days'].' '.($range['days'] === 1 ? 'dzień' : 'dni'),
                    url: route('office-hours.index', ['month' => $month]),
                    badge: 'Urlop',
                );
                $emitted++;
            }
        }
    }

    /**
     * @param  Collection<int, OfficeWorkDay>  $days
     * @return list<array{start: CarbonImmutable, end: CarbonImmutable, days: int}>
     */
    protected function consecutiveRanges(Collection $days): array
    {
        $dates = $days
            ->map(fn (OfficeWorkDay $day) => CarbonImmutable::parse($day->work_date)->startOfDay())
            ->unique(fn (CarbonImmutable $d) => $d->toDateString())
            ->sortBy(fn (CarbonImmutable $d) => $d->timestamp)
            ->values();

        if ($dates->isEmpty()) {
            return [];
        }

        $ranges = [];
        $start = $dates->first();
        $prev = $start;

        foreach ($dates->slice(1) as $current) {
            if ((int) round($prev->diffInDays($current)) === 1) {
                $prev = $current;
                continue;
            }

            $ranges[] = [
                'start' => $start,
                'end' => $prev,
                'days' => (int) $start->diffInDays($prev) + 1,
            ];
            $start = $current;
            $prev = $current;
        }

        $ranges[] = [
            'start' => $start,
            'end' => $prev,
            'days' => (int) $start->diffInDays($prev) + 1,
        ];

        return $ranges;
    }
}
