<?php

namespace App\Http\Controllers;

use App\Services\RecruitmentAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecruitmentAnalyticsController extends Controller
{
    /** Preset ranges offered above the report, in days. `all` reaches back to the first lead. */
    private const PRESETS = [
        '7d' => ['label' => '7 dni', 'days' => 7],
        '30d' => ['label' => '30 dni', 'days' => 30],
        '90d' => ['label' => '90 dni', 'days' => 90],
        '12m' => ['label' => '12 miesięcy', 'days' => 365],
        'all' => ['label' => 'Wszystko', 'days' => null],
    ];

    public function index(Request $request, RecruitmentAnalyticsService $analytics): View
    {
        $preset = $request->string('range')->value();
        if (! array_key_exists($preset, self::PRESETS)) {
            $preset = '90d';
        }

        $to = now()->endOfDay();
        $days = self::PRESETS[$preset]['days'];
        $from = $days === null
            ? Carbon::parse(
                \Illuminate\Support\Facades\DB::table('recruitment_leads')->min('created_at') ?? now()->subYears(5)
            )->startOfDay()
            : now()->subDays($days)->startOfDay();

        return view('recruitment.analytics', [
            'data' => $analytics->build($from, $to),
            'analytics' => $analytics,
            'presets' => self::PRESETS,
            'preset' => $preset,
            'from' => $from,
            'to' => $to,
        ]);
    }
}
