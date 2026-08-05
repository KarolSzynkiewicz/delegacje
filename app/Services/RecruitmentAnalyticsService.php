<?php

namespace App\Services;

use App\Enums\RecruitmentContactOutcome;
use App\Enums\RecruitmentRejectionReason;
use App\Enums\RecruitmentReferralSource;
use App\Enums\RecruitmentStatus;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Read-only analytics over the recruitment domain (leads → processes → contact
 * attempts → status history).
 *
 * Two caveats drive most of the design here:
 *
 * 1. A large part of the data came from bulk imports which wrote the lead and its
 *    single "contact attempt" in the same transaction. Those rows would report a
 *    near-zero response time and silently make the SLA look perfect, so anything
 *    time-to-first-contact related ignores attempts logged within
 *    {@see self::ARTIFACT_SECONDS} of the lead.
 *
 * 2. Some referral sources are not acquisition at all — they are existing employees
 *    backfilled into the pipeline ({@see self::SYNTHETIC_SOURCES}). Counting them as
 *    "hires" would invent a conversion rate that never happened, so channel figures
 *    keep them separate.
 */
class RecruitmentAnalyticsService
{
    /** A "call" logged this soon after the lead is an import artifact, not outreach. */
    public const ARTIFACT_SECONDS = 5;

    /** Sources that represent backfilled/known people rather than inbound acquisition. */
    public const SYNTHETIC_SOURCES = ['system_backfill', 'employee_lifecycle', 'historical_import'];

    /** A process sitting in active outreach untouched for this long is going stale. */
    public const STALE_DAYS = 14;

    /** A brand new lead nobody called within this many days missed the window. */
    public const NEW_LEAD_SLA_DAYS = 3;

    public function build(CarbonInterface $from, CarbonInterface $to): array
    {
        $headline = $this->headline($from, $to);
        $funnel = $this->funnel($from, $to);
        $response = $this->responseTime($from, $to);
        $workQueue = $this->workQueue();
        $dataQuality = $this->dataQuality();

        return [
            'headline' => $headline,
            'funnel' => $funnel,
            'response' => $response,
            'callsByDay' => $this->callsByRecruiterAndDay($from, $to),
            'sources' => $this->bySource($from, $to),
            'recruiters' => $this->byRecruiter($from, $to),
            'trend' => $this->monthlyTrend(12),
            'workQueue' => $workQueue,
            'ownerQueue' => $this->ownerWorkQueue(),
            'rejections' => $this->rejectionReasons($from, $to),
            'outcomes' => $this->outcomeBreakdown($from, $to),
            'callHeatmap' => $this->callHeatmap($from, $to),
            'dataQuality' => $dataQuality,
            'insights' => $this->insights($headline, $funnel, $response, $workQueue, $dataQuality),
            'recommendations' => $this->recommendations($headline, $dataQuality),
        ];
    }

    /** Headline counters for the selected period. */
    public function headline(CarbonInterface $from, CarbonInterface $to): array
    {
        $leads = DB::table('recruitment_leads')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $syntheticLeads = DB::table('recruitment_leads')
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('referral_source', self::SYNTHETIC_SOURCES)
            ->count();

        // Cohort view: of the leads that arrived in the period, how many got a call at all.
        $contacted = DB::table('recruitment_processes as p')
            ->join('recruitment_leads as l', 'l.id', '=', 'p.lead_id')
            ->whereBetween('l.created_at', [$from, $to])
            ->whereExists(fn ($q) => $q->select(DB::raw(1))
                ->from('recruitment_contact_attempts as ca')
                ->whereColumn('ca.recruitment_process_id', 'p.id'))
            ->count();

        // Activity view: calls actually logged in the period, regardless of lead age.
        $callsMade = DB::table('recruitment_contact_attempts')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $callsAnswered = DB::table('recruitment_contact_attempts')
            ->whereBetween('created_at', [$from, $to])
            ->where('outcome', RecruitmentContactOutcome::Odebrano->value)
            ->count();

        $processesTouched = DB::table('recruitment_contact_attempts')
            ->whereBetween('created_at', [$from, $to])
            ->distinct()
            ->count('recruitment_process_id');

        $hired = $this->stageEvents(RecruitmentStatus::Zatrudniony, $from, $to);
        $rejected = $this->stageEvents(RecruitmentStatus::Odrzucony, $from, $to);

        // Hires that are really backfills of people already employed.
        $hiredSynthetic = DB::table('recruitment_status_history as sh')
            ->join('recruitment_processes as p', 'p.id', '=', 'sh.recruitment_process_id')
            ->join('recruitment_leads as l', 'l.id', '=', 'p.lead_id')
            ->where('sh.to_status', RecruitmentStatus::Zatrudniony->value)
            ->whereBetween('sh.created_at', [$from, $to])
            ->whereIn('l.referral_source', self::SYNTHETIC_SOURCES)
            ->count();

        return [
            'leads' => $leads,
            'leads_synthetic' => $syntheticLeads,
            'leads_real' => max(0, $leads - $syntheticLeads),
            'contacted' => $contacted,
            'contact_rate' => $this->pct($contacted, $leads),
            'calls_made' => $callsMade,
            'calls_answered' => $callsAnswered,
            'answer_rate' => $this->pct($callsAnswered, $callsMade),
            'processes_touched' => $processesTouched,
            'calls_per_process' => $processesTouched > 0 ? round($callsMade / $processesTouched, 2) : 0.0,
            'hired' => $hired,
            'hired_synthetic' => $hiredSynthetic,
            'hired_real' => max(0, $hired - $hiredSynthetic),
            'rejected' => $rejected,
            'conversion' => $this->pct($hired, $leads),
        ];
    }

    /**
     * Cohort funnel: of the leads created in the period, how far did each one get.
     * Stage membership is "ever reached", read from status history, so a candidate
     * who was rejected after verification still counts as having reached verification.
     *
     * Backfilled sources are excluded — those rows are people who were already employed
     * and were inserted straight at "hired", so leaving them in would show a funnel that
     * converts better at the bottom than at the top.
     */
    public function funnel(CarbonInterface $from, CarbonInterface $to): array
    {
        $acquisition = fn ($q) => $q->where(fn ($w) => $w
            ->whereNull('l.referral_source')
            ->orWhereNotIn('l.referral_source', self::SYNTHETIC_SOURCES));

        $base = fn () => DB::table('recruitment_processes as p')
            ->join('recruitment_leads as l', 'l.id', '=', 'p.lead_id')
            ->whereBetween('l.created_at', [$from, $to])
            ->tap($acquisition);

        $leads = $base()->count();

        $reached = fn (RecruitmentStatus $status) => $base()
            ->whereExists(fn ($q) => $q->select(DB::raw(1))
                ->from('recruitment_status_history as sh')
                ->whereColumn('sh.recruitment_process_id', 'p.id')
                ->where('sh.to_status', $status->value))
            ->count();

        $withAttempt = $base()
            ->whereExists(fn ($q) => $q->select(DB::raw(1))
                ->from('recruitment_contact_attempts as ca')
                ->whereColumn('ca.recruitment_process_id', 'p.id'))
            ->count();

        $answered = $base()
            ->whereExists(fn ($q) => $q->select(DB::raw(1))
                ->from('recruitment_contact_attempts as ca')
                ->whereColumn('ca.recruitment_process_id', 'p.id')
                ->where('ca.outcome', RecruitmentContactOutcome::Odebrano->value))
            ->count();

        $stages = [
            ['key' => 'leads', 'label' => 'Leady', 'hint' => 'Zgłoszenia, które wpadły w okresie', 'count' => $leads],
            ['key' => 'called', 'label' => 'Podjęto kontakt', 'hint' => 'Co najmniej jedna próba kontaktu', 'count' => $withAttempt],
            ['key' => 'answered', 'label' => 'Rozmowa doszła do skutku', 'hint' => 'Co najmniej raz odebrał', 'count' => $answered],
            ['key' => 'verified', 'label' => 'Weryfikacja', 'hint' => 'Dotarł do statusu Weryfikacja', 'count' => $reached(RecruitmentStatus::Zaakceptowany)],
            ['key' => 'onboarding', 'label' => 'Onboarding', 'hint' => 'Dotarł do onboardingu', 'count' => $reached(RecruitmentStatus::Onboarding)],
            ['key' => 'hired', 'label' => 'Zatrudniony', 'hint' => 'Domknięte zatrudnienie', 'count' => $reached(RecruitmentStatus::Zatrudniony)],
        ];

        // A funnel must not widen. Statuses can be skipped (a process may jump straight
        // to "hired"), so a later stage can out-count an earlier one; treat reaching a
        // stage as having passed every stage before it.
        for ($i = count($stages) - 2; $i >= 0; $i--) {
            $stages[$i]['count'] = max($stages[$i]['count'], $stages[$i + 1]['count']);
        }

        $previous = null;
        foreach ($stages as $i => $stage) {
            $stages[$i]['of_leads'] = $this->pct($stage['count'], $leads);
            $stages[$i]['step_rate'] = $previous === null ? null : $this->pct($stage['count'], $previous);
            $stages[$i]['lost'] = $previous === null ? null : max(0, $previous - $stage['count']);
            $previous = $stage['count'];
        }

        // The step that loses the most people is where the pipeline actually breaks.
        $worst = null;
        foreach ($stages as $stage) {
            if ($stage['lost'] === null || $stage['lost'] <= 0) {
                continue;
            }
            if ($worst === null || $stage['lost'] > $worst['lost']) {
                $worst = $stage;
            }
        }

        return ['stages' => $stages, 'bottleneck' => $worst];
    }

    /**
     * Time from lead arriving to the first genuine outreach attempt.
     * Import artifacts (attempt written together with the lead) are dropped.
     */
    public function responseTime(CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = DB::table('recruitment_processes as p')
            ->join('recruitment_leads as l', 'l.id', '=', 'p.lead_id')
            ->joinSub(
                DB::table('recruitment_contact_attempts')
                    ->select('recruitment_process_id', DB::raw('MIN(created_at) as first_at'))
                    ->groupBy('recruitment_process_id'),
                'fc',
                'fc.recruitment_process_id',
                '=',
                'p.id'
            )
            ->whereBetween('l.created_at', [$from, $to])
            ->whereRaw('TIMESTAMPDIFF(SECOND, l.created_at, fc.first_at) > ?', [self::ARTIFACT_SECONDS])
            ->selectRaw('TIMESTAMPDIFF(MINUTE, l.created_at, fc.first_at) as minutes')
            ->pluck('minutes')
            ->map(fn ($m) => (int) $m)
            ->sort()
            ->values()
            ->all();

        $artifacts = DB::table('recruitment_processes as p')
            ->join('recruitment_leads as l', 'l.id', '=', 'p.lead_id')
            ->joinSub(
                DB::table('recruitment_contact_attempts')
                    ->select('recruitment_process_id', DB::raw('MIN(created_at) as first_at'))
                    ->groupBy('recruitment_process_id'),
                'fc',
                'fc.recruitment_process_id',
                '=',
                'p.id'
            )
            ->whereBetween('l.created_at', [$from, $to])
            ->whereRaw('TIMESTAMPDIFF(SECOND, l.created_at, fc.first_at) <= ?', [self::ARTIFACT_SECONDS])
            ->count();

        $total = count($rows);
        $within = fn (int $minutes) => $total === 0
            ? null
            : $this->pct(count(array_filter($rows, fn ($m) => $m <= $minutes)), $total);

        return [
            'sample' => $total,
            'excluded_artifacts' => $artifacts,
            'median_minutes' => $this->percentile($rows, 0.5),
            'p90_minutes' => $this->percentile($rows, 0.9),
            'avg_minutes' => $total > 0 ? (int) round(array_sum($rows) / $total) : null,
            'within_1h' => $within(60),
            'within_24h' => $within(60 * 24),
            'within_72h' => $within(60 * 72),
        ];
    }

    /** Channel performance — where good candidates actually come from. */
    public function bySource(CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = DB::table('recruitment_leads as l')
            ->join('recruitment_processes as p', 'p.lead_id', '=', 'l.id')
            ->whereBetween('l.created_at', [$from, $to])
            ->groupBy('l.referral_source')
            ->selectRaw('l.referral_source as source, COUNT(*) as leads')
            ->selectRaw('SUM(EXISTS(SELECT 1 FROM recruitment_contact_attempts ca WHERE ca.recruitment_process_id = p.id)) as contacted')
            ->selectRaw('SUM(EXISTS(SELECT 1 FROM recruitment_contact_attempts ca WHERE ca.recruitment_process_id = p.id AND ca.outcome = ?)) as answered', [RecruitmentContactOutcome::Odebrano->value])
            ->selectRaw('SUM(p.status = ?) as hired', [RecruitmentStatus::Zatrudniony->value])
            ->selectRaw('SUM(p.status = ?) as rejected', [RecruitmentStatus::Odrzucony->value])
            ->selectRaw('SUM(EXISTS(SELECT 1 FROM recruitment_status_history sh WHERE sh.recruitment_process_id = p.id AND sh.to_status = ?)) as verified', [RecruitmentStatus::Zaakceptowany->value])
            ->orderByDesc('leads')
            ->get();

        return $rows->map(function ($r) {
            $source = $r->source ? RecruitmentReferralSource::tryFrom($r->source) : null;

            return [
                'source' => $r->source,
                'label' => $source?->label() ?? 'Nieokreślone',
                'is_synthetic' => in_array($r->source, self::SYNTHETIC_SOURCES, true),
                'leads' => (int) $r->leads,
                'contacted' => (int) $r->contacted,
                'contact_rate' => $this->pct((int) $r->contacted, (int) $r->leads),
                'answered' => (int) $r->answered,
                'answer_rate' => $this->pct((int) $r->answered, (int) $r->contacted),
                'verified' => (int) $r->verified,
                'hired' => (int) $r->hired,
                'rejected' => (int) $r->rejected,
                'conversion' => $this->pct((int) $r->hired, (int) $r->leads),
            ];
        })->all();
    }

    /**
     * Recruiter activity. Attribution follows who logged the call / flipped the status,
     * not `assigned_recruiter_id` — ownership is barely filled in, calls always are.
     */
    public function byRecruiter(CarbonInterface $from, CarbonInterface $to): array
    {
        $calls = DB::table('recruitment_contact_attempts as ca')
            ->leftJoin('users as u', 'u.id', '=', 'ca.user_id')
            ->whereBetween('ca.created_at', [$from, $to])
            ->groupBy('ca.user_id', 'u.name')
            ->selectRaw('ca.user_id, COALESCE(u.name, ?) as name, COUNT(*) as calls', ['Nieprzypisany'])
            ->selectRaw('COUNT(DISTINCT ca.recruitment_process_id) as processes')
            ->selectRaw('SUM(ca.outcome = ?) as answered', [RecruitmentContactOutcome::Odebrano->value])
            ->get()
            ->keyBy('user_id');

        $transitions = DB::table('recruitment_status_history')
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('changed_by')
            ->groupBy('changed_by')
            ->selectRaw('changed_by, COUNT(*) as transitions')
            ->selectRaw('SUM(to_status = ?) as hired', [RecruitmentStatus::Zatrudniony->value])
            ->selectRaw('SUM(to_status = ?) as rejected', [RecruitmentStatus::Odrzucony->value])
            ->selectRaw('SUM(to_status = ?) as verified', [RecruitmentStatus::Zaakceptowany->value])
            ->get()
            ->keyBy('changed_by');

        $userIds = $calls->keys()->merge($transitions->keys())->unique()->filter()->values();
        $names = DB::table('users')->whereIn('id', $userIds)->pluck('name', 'id');

        $rows = [];
        foreach ($userIds as $id) {
            $c = $calls->get($id);
            $t = $transitions->get($id);

            $callCount = (int) ($c->calls ?? 0);
            $processes = (int) ($c->processes ?? 0);
            $answered = (int) ($c->answered ?? 0);

            $rows[] = [
                'user_id' => $id,
                'name' => $names[$id] ?? ($c->name ?? 'Nieprzypisany'),
                'calls' => $callCount,
                'processes' => $processes,
                'answered' => $answered,
                'answer_rate' => $this->pct($answered, $callCount),
                'transitions' => (int) ($t->transitions ?? 0),
                'verified' => (int) ($t->verified ?? 0),
                'rejected' => (int) ($t->rejected ?? 0),
                'hired' => (int) ($t->hired ?? 0),
                // Of the people this recruiter actually reached, how many moved forward.
                'conversion' => $this->pct((int) ($t->verified ?? 0), $answered),
            ];
        }

        usort($rows, fn ($a, $b) => $b['calls'] <=> $a['calls']);

        return $rows;
    }

    /** Leads / calls / hires per month — the shape of the operation over time. */
    public function monthlyTrend(int $months): array
    {
        $since = now()->startOfMonth()->subMonths($months - 1);

        $leads = DB::table('recruitment_leads')
            ->where('created_at', '>=', $since)
            ->groupBy('ym')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as n")
            ->pluck('n', 'ym');

        $calls = DB::table('recruitment_contact_attempts')
            ->where('created_at', '>=', $since)
            ->groupBy('ym')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as n")
            ->pluck('n', 'ym');

        $hires = DB::table('recruitment_status_history')
            ->where('created_at', '>=', $since)
            ->where('to_status', RecruitmentStatus::Zatrudniony->value)
            ->groupBy('ym')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as n")
            ->pluck('n', 'ym');

        $out = [];
        for ($i = 0; $i < $months; $i++) {
            $month = $since->copy()->addMonths($i);
            $key = $month->format('Y-m');
            $out[] = [
                'ym' => $key,
                'label' => $month->translatedFormat('M Y'),
                'leads' => (int) ($leads[$key] ?? 0),
                'calls' => (int) ($calls[$key] ?? 0),
                'hires' => (int) ($hires[$key] ?? 0),
            ];
        }

        return $out;
    }

    /**
     * Call volume per recruiter per calendar day in the selected period.
     *
     * @return array{
     *     days: array<int, array{key: string, label: string}>,
     *     outcome_order: array<int, RecruitmentContactOutcome>,
     *     rows: array<int, array{
     *         user_id: int|null,
     *         name: string,
     *         by_day: array<string, array{total: int, outcomes: array<string, int>}>,
     *         total: int,
     *         outcomes: array<string, int>
     *     }>,
     *     grand_total: int,
     *     grand_outcomes: array<string, int>
     * }
     */
    public function callsByRecruiterAndDay(CarbonInterface $from, CarbonInterface $to): array
    {
        $outcomeOrder = RecruitmentContactOutcome::cases();
        $outcomeValues = array_map(fn (RecruitmentContactOutcome $o) => $o->value, $outcomeOrder);

        $counts = DB::table('recruitment_contact_attempts as ca')
            ->leftJoin('users as u', 'u.id', '=', 'ca.user_id')
            ->whereBetween('ca.created_at', [$from, $to])
            ->groupBy('ca.user_id', 'u.name', 'day', 'ca.outcome')
            ->selectRaw('ca.user_id, COALESCE(u.name, ?) as name, DATE(ca.created_at) as day, ca.outcome, COUNT(*) as n', ['Nieprzypisany'])
            ->get();

        $days = [];
        $current = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();
        while ($current <= $end) {
            $key = $current->format('Y-m-d');
            $days[] = [
                'key' => $key,
                'label' => $current->format('d.m'),
            ];
            $current->addDay();
        }

        $emptyOutcomes = fn (): array => array_fill_keys($outcomeValues, 0);
        $dayKeys = array_column($days, 'key');
        $makeEmptyByDay = function () use ($dayKeys, $emptyOutcomes): array {
            $byDay = [];
            foreach ($dayKeys as $key) {
                $byDay[$key] = ['total' => 0, 'outcomes' => $emptyOutcomes()];
            }

            return $byDay;
        };

        $byUser = [];
        foreach ($counts as $row) {
            $userId = $row->user_id;
            if (! isset($byUser[$userId])) {
                $byUser[$userId] = [
                    'user_id' => $userId !== null ? (int) $userId : null,
                    'name' => $row->name,
                    'by_day' => $makeEmptyByDay(),
                    'total' => 0,
                    'outcomes' => $emptyOutcomes(),
                ];
            }

            $dayKey = $row->day;
            $outcome = $row->outcome;
            $n = (int) $row->n;

            if (! isset($byUser[$userId]['by_day'][$dayKey])) {
                $byUser[$userId]['by_day'][$dayKey] = ['total' => 0, 'outcomes' => $emptyOutcomes()];
            }

            $byUser[$userId]['by_day'][$dayKey]['total'] += $n;
            if (isset($byUser[$userId]['by_day'][$dayKey]['outcomes'][$outcome])) {
                $byUser[$userId]['by_day'][$dayKey]['outcomes'][$outcome] += $n;
            }
            $byUser[$userId]['total'] += $n;
            if (isset($byUser[$userId]['outcomes'][$outcome])) {
                $byUser[$userId]['outcomes'][$outcome] += $n;
            }
        }

        $rows = array_values($byUser);
        usort($rows, fn ($a, $b) => $b['total'] <=> $a['total']);

        $grandOutcomes = $emptyOutcomes();
        foreach ($rows as $row) {
            foreach ($outcomeValues as $value) {
                $grandOutcomes[$value] += $row['outcomes'][$value] ?? 0;
            }
        }

        return [
            'days' => $days,
            'outcome_order' => $outcomeOrder,
            'rows' => $rows,
            'grand_total' => array_sum(array_column($rows, 'total')),
            'grand_outcomes' => $grandOutcomes,
        ];
    }

    /**
     * Current pipeline snapshot by assigned owner and stage.
     * Same active statuses as {@see workQueue()}, but rows are recruiters.
     *
     * @return array{statuses: array<int, RecruitmentStatus>, rows: array<int, array{user_id: int|null, name: string, total: int, by_status: array<string, int>}>}
     */
    public function ownerWorkQueue(): array
    {
        $statuses = [
            RecruitmentStatus::Nowy,
            RecruitmentStatus::WTrakcieKontaktu,
            RecruitmentStatus::Zaakceptowany,
            RecruitmentStatus::Onboarding,
        ];

        $statusValues = array_map(fn (RecruitmentStatus $s) => $s->value, $statuses);

        $query = DB::table('recruitment_processes as p')
            ->leftJoin('users as u', 'u.id', '=', 'p.assigned_recruiter_id')
            ->whereIn('p.status', $statusValues)
            ->groupBy('p.assigned_recruiter_id', 'u.name')
            ->selectRaw('p.assigned_recruiter_id as user_id, COALESCE(u.name, ?) as name', ['Nieprzypisany'])
            ->selectRaw('COUNT(*) as total');

        foreach ($statuses as $status) {
            $query->selectRaw(
                'SUM(p.status = ?) as status_'.$status->value,
                [$status->value]
            );
        }

        $rows = $query->orderByDesc('total')->get()->map(function ($row) use ($statuses) {
            $byStatus = [];
            foreach ($statuses as $status) {
                $key = 'status_'.$status->value;
                $byStatus[$status->value] = (int) ($row->{$key} ?? 0);
            }

            return [
                'user_id' => $row->user_id !== null ? (int) $row->user_id : null,
                'name' => $row->name,
                'total' => (int) $row->total,
                'by_status' => $byStatus,
            ];
        })->all();

        return [
            'statuses' => $statuses,
            'rows' => $rows,
            'grand_total' => array_sum(array_column($rows, 'total')),
        ];
    }

    /**
     * Snapshot of what is sitting in the pipeline right now and rotting.
     * Deliberately not period-filtered — a stale lead is stale regardless of the filter.
     */
    public function workQueue(): array
    {
        $byStatus = DB::table('recruitment_processes')
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as n')
            ->pluck('n', 'status');

        $neverCalled = DB::table('recruitment_processes as p')
            ->whereIn('p.status', [RecruitmentStatus::Nowy->value, RecruitmentStatus::WTrakcieKontaktu->value])
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))
                ->from('recruitment_contact_attempts as ca')
                ->whereColumn('ca.recruitment_process_id', 'p.id'))
            ->count();

        // Age is measured from the last real activity, falling back to when the lead
        // arrived. Process rows were all written on import day, so their own created_at
        // would report a mass-imported 2023 lead as brand new.
        $lastTouch = 'COALESCE((SELECT MAX(ca.created_at) FROM recruitment_contact_attempts ca WHERE ca.recruitment_process_id = p.id), l.created_at)';

        $newOverSla = DB::table('recruitment_processes as p')
            ->join('recruitment_leads as l', 'l.id', '=', 'p.lead_id')
            ->where('p.status', RecruitmentStatus::Nowy->value)
            ->whereRaw("{$lastTouch} < ?", [now()->subDays(self::NEW_LEAD_SLA_DAYS)])
            ->count();

        $activeTotal = (int) ($byStatus[RecruitmentStatus::WTrakcieKontaktu->value] ?? 0);

        $stale = DB::table('recruitment_processes as p')
            ->join('recruitment_leads as l', 'l.id', '=', 'p.lead_id')
            ->where('p.status', RecruitmentStatus::WTrakcieKontaktu->value)
            ->whereRaw("{$lastTouch} < ?", [now()->subDays(self::STALE_DAYS)])
            ->count();

        $buckets = DB::table('recruitment_processes as p')
            ->join('recruitment_leads as l', 'l.id', '=', 'p.lead_id')
            ->whereIn('p.status', [RecruitmentStatus::Nowy->value, RecruitmentStatus::WTrakcieKontaktu->value, RecruitmentStatus::Zaakceptowany->value, RecruitmentStatus::Onboarding->value])
            ->selectRaw("p.status, CASE
                WHEN DATEDIFF(NOW(), {$lastTouch}) <= 3 THEN '0-3 dni'
                WHEN DATEDIFF(NOW(), {$lastTouch}) <= 7 THEN '4-7 dni'
                WHEN DATEDIFF(NOW(), {$lastTouch}) <= 14 THEN '8-14 dni'
                WHEN DATEDIFF(NOW(), {$lastTouch}) <= 30 THEN '15-30 dni'
                ELSE '30+ dni' END as bucket, COUNT(*) as n")
            ->groupBy('p.status', 'bucket')
            ->get();

        $order = ['0-3 dni', '4-7 dni', '8-14 dni', '15-30 dni', '30+ dni'];
        $matrix = [];
        foreach ([RecruitmentStatus::Nowy, RecruitmentStatus::WTrakcieKontaktu, RecruitmentStatus::Zaakceptowany, RecruitmentStatus::Onboarding] as $status) {
            $row = ['status' => $status, 'total' => 0, 'buckets' => array_fill_keys($order, 0)];
            foreach ($buckets->where('status', $status->value) as $b) {
                $row['buckets'][$b->bucket] = (int) $b->n;
                $row['total'] += (int) $b->n;
            }
            $matrix[] = $row;
        }

        return [
            'by_status' => $byStatus,
            'never_called' => $neverCalled,
            'new_over_sla' => $newOverSla,
            'stale' => $stale,
            'stale_share' => $this->pct($stale, $activeTotal),
            'active_total' => $activeTotal,
            'bucket_order' => $order,
            'matrix' => $matrix,
        ];
    }

    /** Why we say no — only useful once recruiters actually pick a reason. */
    public function rejectionReasons(CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = DB::table('recruitment_status_history as sh')
            ->join('recruitment_processes as p', 'p.id', '=', 'sh.recruitment_process_id')
            ->where('sh.to_status', RecruitmentStatus::Odrzucony->value)
            ->whereBetween('sh.created_at', [$from, $to])
            ->groupBy('p.rejection_reason')
            ->selectRaw('p.rejection_reason as reason, COUNT(*) as n')
            ->orderByDesc('n')
            ->get();

        $total = $rows->sum('n');

        return [
            'total' => (int) $total,
            'rows' => $rows->map(fn ($r) => [
                'reason' => $r->reason,
                'label' => $r->reason
                    ? (RecruitmentRejectionReason::tryFrom($r->reason)?->label() ?? $r->reason)
                    : 'Nie podano powodu',
                'n' => (int) $r->n,
                'pct' => $this->pct((int) $r->n, (int) $total),
            ])->all(),
        ];
    }

    /** What happens when we dial. */
    public function outcomeBreakdown(CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = DB::table('recruitment_contact_attempts')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('outcome')
            ->selectRaw('outcome, COUNT(*) as n')
            ->orderByDesc('n')
            ->get();

        $total = $rows->sum('n');

        return [
            'total' => (int) $total,
            'rows' => $rows->map(fn ($r) => [
                'outcome' => $r->outcome,
                'label' => RecruitmentContactOutcome::tryFrom($r->outcome)?->label() ?? $r->outcome,
                'variant' => RecruitmentContactOutcome::tryFrom($r->outcome)?->variant() ?? 'secondary',
                'n' => (int) $r->n,
                'pct' => $this->pct((int) $r->n, (int) $total),
            ])->all(),
        ];
    }

    /**
     * When do we call, and when do people actually pick up? Answers "call at 10:00,
     * not 16:00". Midnight-heavy rows are imports with a date but no clock time.
     */
    public function callHeatmap(CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = DB::table('recruitment_contact_attempts as ca')
            ->join('recruitment_processes as p', 'p.id', '=', 'ca.recruitment_process_id')
            ->join('recruitment_leads as l', 'l.id', '=', 'p.lead_id')
            ->whereBetween('ca.created_at', [$from, $to])
            ->whereRaw('HOUR(ca.created_at) BETWEEN 6 AND 20')
            ->whereRaw('ABS(TIMESTAMPDIFF(SECOND, l.created_at, ca.created_at)) > ?', [self::ARTIFACT_SECONDS])
            ->groupBy('hour')
            ->selectRaw('HOUR(ca.created_at) as hour, COUNT(*) as n')
            ->selectRaw('SUM(ca.outcome = ?) as answered', [RecruitmentContactOutcome::Odebrano->value])
            ->orderBy('hour')
            ->get();

        return $rows->map(fn ($r) => [
            'hour' => (int) $r->hour,
            'calls' => (int) $r->n,
            'answered' => (int) $r->answered,
            'answer_rate' => $this->pct((int) $r->answered, (int) $r->n),
        ])->all();
    }

    /** How much the numbers above can be trusted. */
    public function dataQuality(): array
    {
        $processes = DB::table('recruitment_processes')->count();
        $candidates = DB::table('recruitment_candidates')->count();

        $unassigned = DB::table('recruitment_processes')->whereNull('assigned_recruiter_id')->count();

        $rejectedTotal = DB::table('recruitment_processes')
            ->where('status', RecruitmentStatus::Odrzucony->value)
            ->count();
        $rejectedNoReason = DB::table('recruitment_processes')
            ->where('status', RecruitmentStatus::Odrzucony->value)
            ->where(fn ($q) => $q->whereNull('rejection_reason')->orWhere('rejection_reason', RecruitmentRejectionReason::Inne->value))
            ->count();

        $artifactCalls = DB::table('recruitment_contact_attempts as ca')
            ->join('recruitment_processes as p', 'p.id', '=', 'ca.recruitment_process_id')
            ->join('recruitment_leads as l', 'l.id', '=', 'p.lead_id')
            ->whereRaw('ABS(TIMESTAMPDIFF(SECOND, l.created_at, ca.created_at)) <= ?', [self::ARTIFACT_SECONDS])
            ->count();
        $allCalls = DB::table('recruitment_contact_attempts')->count();

        $noPhone = DB::table('recruitment_candidates')->whereNull('phone')->orWhere('phone', '')->count();
        $activeConsents = DB::table('recruitment_consents')->whereNull('withdrawn_at')->count();

        return [
            'processes' => $processes,
            'candidates' => $candidates,
            'unassigned' => $unassigned,
            'unassigned_pct' => $this->pct($unassigned, $processes),
            'rejected_no_reason' => $rejectedNoReason,
            'rejected_no_reason_pct' => $this->pct($rejectedNoReason, $rejectedTotal),
            'artifact_calls' => $artifactCalls,
            'artifact_calls_pct' => $this->pct($artifactCalls, $allCalls),
            'no_phone' => $noPhone,
            'no_phone_pct' => $this->pct($noPhone, $candidates),
            'active_consents' => $activeConsents,
            'consent_coverage_pct' => $this->pct($activeConsents, $candidates),
        ];
    }

    /**
     * Rule-based findings — the "so what" layer, so the page states the problem
     * instead of leaving the reader to spot it in a table.
     *
     * @return array<int, array{severity: string, title: string, body: string}>
     */
    private function insights(array $headline, array $funnel, array $response, array $workQueue, array $quality): array
    {
        $out = [];

        if ($workQueue['stale'] > 0) {
            $out[] = [
                'severity' => $workQueue['stale_share'] >= 40 ? 'danger' : 'warning',
                'title' => "{$workQueue['stale']} procesów w kontakcie leży bez ruchu ponad ".self::STALE_DAYS.' dni',
                'body' => "To {$workQueue['stale_share']}% wszystkich aktywnych rozmów. Każdy dzień zwłoki to kandydat, który w tym czasie przyjmuje ofertę gdzie indziej — to najtańszy do odzyskania zasób, jaki macie.",
            ];
        }

        if ($workQueue['never_called'] > 0) {
            $out[] = [
                'severity' => 'warning',
                'title' => "{$workQueue['never_called']} otwartych procesów nigdy nie doczekało się telefonu",
                'body' => 'Leady, za które zapłaciliście, ale nikt ich nie tknął. Zero kosztu krańcowego, żeby to naprawić — wystarczy wykonać połączenie.',
            ];
        }

        if ($headline['hired_real'] === 0 && $headline['leads_real'] > 0) {
            $out[] = [
                'severity' => 'danger',
                'title' => 'Zero zatrudnień z leadów pozyskanych w tym okresie',
                'body' => "W okresie wpadło {$headline['leads_real']} realnych leadów i żaden nie zakończył się zatrudnieniem. Wszystkie zatrudnienia w bazie ({$headline['hired_synthetic']}) to backfill osób już pracujących, a nie efekt lejka rekrutacyjnego.",
            ];
        }

        if ($funnel['bottleneck'] && $funnel['bottleneck']['lost'] > 0) {
            $b = $funnel['bottleneck'];
            $out[] = [
                'severity' => 'info',
                'title' => "Największy ubytek: {$b['label']} (tracicie {$b['lost']} osób na tym kroku)",
                'body' => "Do tego etapu dochodzi tylko {$b['step_rate']}% z poprzedniego. Tu leży najwięcej do ugrania — poprawa o kilka punktów procentowych na tym jednym kroku daje więcej niż zwiększanie budżetu na leady.",
            ];
        }

        if ($quality['rejected_no_reason_pct'] >= 50) {
            $out[] = [
                'severity' => 'warning',
                'title' => "{$quality['rejected_no_reason_pct']}% odrzuceń nie ma konkretnego powodu",
                'body' => 'Bez powodu odrzucenia nie da się odpowiedzieć, czy tracicie ludzi przez stawkę, brak doświadczenia czy złe dane kontaktowe. To jedno pole w formularzu, a odblokowuje całą analizę przyczyn.',
            ];
        }

        if ($quality['artifact_calls_pct'] >= 20) {
            $out[] = [
                'severity' => 'info',
                'title' => "{$quality['artifact_calls_pct']}% prób kontaktu pochodzi z importu, nie z realnych rozmów",
                'body' => 'Te wpisy powstały w tej samej sekundzie co lead. Czasy reakcji poniżej liczone są z ich pominięciem, ale wskaźniki wolumenowe (liczba telefonów) nadal je zawierają.',
            ];
        }

        if ($quality['unassigned_pct'] >= 80) {
            $out[] = [
                'severity' => 'warning',
                'title' => "{$quality['unassigned_pct']}% procesów nie ma przypisanego rekrutera",
                'body' => 'Bez właściciela nie da się egzekwować odpowiedzialności ani policzyć realnej wydajności zespołu. Statystyki per rekruter opierają się dziś wyłącznie na tym, kto zarejestrował telefon.',
            ];
        }

        if ($response['sample'] > 0 && $response['within_24h'] !== null && $response['within_24h'] < 80) {
            $out[] = [
                'severity' => 'warning',
                'title' => "Tylko {$response['within_24h']}% leadów dostaje telefon w ciągu doby",
                'body' => 'Mediana czasu reakcji to '.$this->humanMinutes($response['median_minutes']).'. Szybkość pierwszego kontaktu jest najsilniejszym pojedynczym czynnikiem skuteczności — kandydat aplikuje w kilka miejsc naraz.',
            ];
        }

        if ($quality['consent_coverage_pct'] < 5) {
            $out[] = [
                'severity' => 'danger',
                'title' => 'Praktycznie brak zarejestrowanych zgód RODO',
                'body' => "Aktywne zgody ma {$quality['active_consents']} z {$quality['candidates']} kandydatów. Przy bazie tej wielkości to realne ryzyko compliance, niezależne od wyników rekrutacji.",
            ];
        }

        if ($headline['answer_rate'] !== null && $headline['answer_rate'] > 0 && $headline['calls_per_process'] <= 1.05 && $headline['processes_touched'] > 0) {
            $out[] = [
                'severity' => 'info',
                'title' => 'Średnio '.number_format($headline['calls_per_process'], 2, ',', ' ').' próby kontaktu na kandydata',
                'body' => 'Praktycznie nikt nie jest obdzwaniany drugi raz. Standardem w rekrutacji wolumenowej są 3 próby w różnych porach dnia — przy obecnym poziomie odrzucacie kandydatów, którzy po prostu nie odebrali.',
            ];
        }

        return $out;
    }

    /**
     * Which missing inputs currently cap what this report can answer.
     *
     * Each entry is gated on the gap actually existing, so the section empties out
     * as the gaps get closed instead of repeating advice that stopped applying.
     *
     * @return array<int, array{title: string, body: string}>
     */
    private function recommendations(array $headline, array $quality): array
    {
        $out = [];

        if (($quality['unassigned_pct'] ?? 0) >= 20) {
            $out[] = [
                'title' => 'Przypisany rekruter',
                'body' => 'Bez właściciela procesu nie ma rozliczalności ani wiarygodnego porównania zespołu — dziś atrybucja opiera się wyłącznie na tym, kto zarejestrował telefon.',
            ];
        }

        if (($quality['rejected_no_reason_pct'] ?? 0) >= 20) {
            $out[] = [
                'title' => 'Konkretny powód odrzucenia',
                'body' => 'Dopóki dominuje „Inne”, nie da się odróżnić kandydata za drogiego od takiego, z którym nie było kontaktu. To dwie różne przyczyny i dwa różne działania naprawcze.',
            ];
        }

        // Nie ma gdzie trzymać kosztów kanału — to luka w schemacie, nie w danych,
        // więc jako jedyna pozycja nie zależy od bieżących wartości.
        $out[] = [
            'title' => 'Koszt pozyskania per kanał',
            'body' => 'Widać, ile leadów daje każde źródło, ale nie ile kosztuje jeden zatrudniony. Bez tego nie da się rozstrzygnąć, który kanał skalować, a który wyłączyć.',
        ];

        if ($headline['processes_touched'] > 0 && $headline['calls_per_process'] <= 1.05) {
            $out[] = [
                'title' => 'Osobny wpis dla każdej próby kontaktu',
                'body' => 'Na kandydata przypada dziś średnio '.number_format($headline['calls_per_process'], 2, ',', ' ').' próby, więc nie da się zmierzyć, czy druga i trzecia się opłacają.',
            ];
        }

        if (($quality['consent_coverage_pct'] ?? 0) < 50) {
            $out[] = [
                'title' => 'Zgoda RODO przy zgłoszeniu',
                'body' => 'Przy obecnym pokryciu ('.$this->formatPct($quality['consent_coverage_pct']).') baza nie nadaje się do ponownego wykorzystania w kampanii bez ryzyka prawnego.',
            ];
        }

        return $out;
    }

    private function formatPct(?float $value): string
    {
        return $value === null ? 'brak danych' : rtrim(rtrim(number_format($value, 1, ',', ' '), '0'), ',').'%';
    }

    private function stageEvents(RecruitmentStatus $status, CarbonInterface $from, CarbonInterface $to): int
    {
        return DB::table('recruitment_status_history')
            ->where('to_status', $status->value)
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    /** @param  array<int, int>  $sorted */
    private function percentile(array $sorted, float $p): ?int
    {
        $n = count($sorted);
        if ($n === 0) {
            return null;
        }

        $index = (int) floor($p * ($n - 1));

        return $sorted[$index];
    }

    private function pct(int $part, int $total): ?float
    {
        if ($total <= 0) {
            return null;
        }

        return round($part * 100 / $total, 1);
    }

    public function humanMinutes(?int $minutes): string
    {
        if ($minutes === null) {
            return 'brak danych';
        }

        if ($minutes < 60) {
            return $minutes.' min';
        }

        if ($minutes < 60 * 48) {
            $h = intdiv($minutes, 60);
            $m = $minutes % 60;

            return $m > 0 ? "{$h} godz. {$m} min" : "{$h} godz.";
        }

        return round($minutes / 1440, 1).' dni';
    }
}
