<?php

namespace App\Http\Controllers;

use App\Services\RecruitmentAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RecruitmentAnalyticsController extends Controller
{
    /** Preset ranges for the long-term section. `all` reaches back to the first lead. */
    private const PRESETS = [
        '7d' => ['label' => '7 dni', 'days' => 7],
        '30d' => ['label' => '30 dni', 'days' => 30],
        '90d' => ['label' => '90 dni', 'days' => 90],
        '12m' => ['label' => '12 miesięcy', 'days' => 365],
        'all' => ['label' => 'Wszystko', 'days' => null],
    ];

    /**
     * Perspectives for the short-term engagement section, navigated one step at a
     * time — same day/week/month idiom as the weekly overview and the hours grid.
     */
    private const PERIODS = [
        'day' => ['label' => 'Dzień', 'perspective' => 'Widok dzienny', 'jump' => 'Dziś', 'prev' => 'Poprzedni dzień', 'next' => 'Następny dzień'],
        'week' => ['label' => 'Tydzień', 'perspective' => 'Widok tygodniowy', 'jump' => 'Ten tydzień', 'prev' => 'Poprzedni tydzień', 'next' => 'Następny tydzień'],
        'month' => ['label' => 'Miesiąc', 'perspective' => 'Widok miesięczny', 'jump' => 'Ten miesiąc', 'prev' => 'Poprzedni miesiąc', 'next' => 'Następny miesiąc'],
    ];

    public function index(Request $request, RecruitmentAnalyticsService $analytics): View
    {
        $period = $request->string('period')->value();
        if (! array_key_exists($period, self::PERIODS)) {
            $period = 'week';
        }

        $anchor = $this->parseAnchor($request);
        [$from, $to] = $this->periodBounds($period, $anchor);

        $preset = $request->string('range')->value();
        if (! array_key_exists($preset, self::PRESETS)) {
            $preset = '90d';
        }
        [$longFrom, $longTo] = $this->presetBounds($preset);

        return view('recruitment.analytics', [
            'engagement' => $analytics->buildEngagement($from, $to),
            'longTerm' => $analytics->buildLongTerm($longFrom, $longTo),
            'analytics' => $analytics,
            'periods' => self::PERIODS,
            'period' => $period,
            'nav' => $this->buildNavigation($period, $anchor, $from, $to, $preset),
            'from' => $from,
            'to' => $to,
            'presets' => self::PRESETS,
            'preset' => $preset,
            'longFrom' => $longFrom,
            'longTo' => $longTo,
        ]);
    }

    /** Anchor day the short-term period is built around; garbage input falls back to today. */
    private function parseAnchor(Request $request): Carbon
    {
        $raw = $request->string('date')->value();

        if ($raw === '') {
            return now()->startOfDay();
        }

        try {
            return Carbon::parse($raw)->startOfDay();
        } catch (\Exception) {
            return now()->startOfDay();
        }
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function periodBounds(string $period, Carbon $anchor): array
    {
        return match ($period) {
            'day' => [$anchor->copy()->startOfDay(), $anchor->copy()->endOfDay()],
            'month' => [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()],
            default => [$anchor->copy()->startOfWeek(), $anchor->copy()->endOfWeek()],
        };
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function presetBounds(string $preset): array
    {
        $days = self::PRESETS[$preset]['days'];

        $from = $days === null
            ? Carbon::parse(DB::table('recruitment_leads')->min('created_at') ?? now()->subYears(5))->startOfDay()
            : now()->subDays($days)->startOfDay();

        return [$from, now()->endOfDay()];
    }

    /**
     * Labels and links for stepping the short-term period back and forth. The
     * long-term preset rides along in every URL so switching one range never
     * silently resets the other.
     */
    private function buildNavigation(string $period, Carbon $anchor, Carbon $from, Carbon $to, string $preset): array
    {
        $url = fn (string $targetPeriod, Carbon $targetAnchor): string => route('recruitment-analytics.index', [
            'period' => $targetPeriod,
            'date' => $targetAnchor->format('Y-m-d'),
            'range' => $preset,
        ]);

        $prevAnchor = match ($period) {
            'day' => $anchor->copy()->subDay(),
            'month' => $anchor->copy()->subMonthNoOverflow(),
            default => $anchor->copy()->subWeek(),
        };

        $nextAnchor = match ($period) {
            'day' => $anchor->copy()->addDay(),
            'month' => $anchor->copy()->addMonthNoOverflow(),
            default => $anchor->copy()->addWeek(),
        };

        [$title, $subtitle] = match ($period) {
            'day' => [
                ucfirst($from->isoFormat('dddd, D MMMM YYYY')),
                $from->isSameDay(now()) ? 'Dzisiejsza aktywność' : 'Pojedynczy dzień',
            ],
            'month' => [
                ucfirst($from->isoFormat('MMMM YYYY')),
                $from->isoFormat('D MMM').' – '.$to->isoFormat('D MMM YYYY'),
            ],
            default => [
                'Tydzień '.$from->isoWeek(),
                $from->isoFormat('D MMM').' – '.$to->isoFormat('D MMM YYYY'),
            ],
        };

        // Period switching keeps the anchor, so jumping day → month stays in the
        // month you were already looking at instead of snapping back to today.
        $switchUrls = [];
        foreach (array_keys(self::PERIODS) as $key) {
            $switchUrls[$key] = $url($key, $anchor);
        }

        $presetUrls = [];
        foreach (array_keys(self::PRESETS) as $key) {
            $presetUrls[$key] = route('recruitment-analytics.index', [
                'period' => $period,
                'date' => $anchor->format('Y-m-d'),
                'range' => $key,
            ]);
        }

        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'perspective' => self::PERIODS[$period]['perspective'],
            'prevUrl' => $url($period, $prevAnchor),
            'nextUrl' => $url($period, $nextAnchor),
            'prevLabel' => self::PERIODS[$period]['prev'],
            'nextLabel' => self::PERIODS[$period]['next'],
            'jumpLabel' => self::PERIODS[$period]['jump'],
            'jumpUrl' => $url($period, now()->startOfDay()),
            'isCurrent' => now()->between($from, $to),
            'switchUrls' => $switchUrls,
            'presetUrls' => $presetUrls,
        ];
    }
}
