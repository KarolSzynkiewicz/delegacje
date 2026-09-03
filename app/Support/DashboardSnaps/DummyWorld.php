<?php

namespace App\Support\DashboardSnaps;

use App\Enums\EmployeeLocationState;
use App\Enums\PayrollStatus;
use App\Enums\RecruitmentCandidateFlag;
use App\Enums\RecruitmentStatus;
use App\Enums\TaskStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Statyczny świat demo na dashboard — te same osoby/projekty we wszystkich snapach.
 */
final class DummyWorld
{
    public function __construct(private Carbon $today) {}

    public static function make(?Carbon $today = null): self
    {
        return new self(($today ?? now())->copy()->startOfDay());
    }

    /**
     * @return array<string, mixed>
     */
    public function toView(): array
    {
        $people = $this->people();
        $weekStart = $this->today->copy()->startOfWeek();

        return [
            'today' => $this->today,
            'weekStart' => $weekStart,
            'weekEnd' => $weekStart->copy()->endOfWeek(),
            'monthStart' => $this->today->copy()->startOfMonth(),
            'monthEnd' => $this->today->copy()->endOfMonth(),
            'people' => $people,
            'weekly' => $this->weekly($people, $weekStart),
            'timeLogs' => $this->timeLogs($people),
            'payrollBatch' => $this->payrollBatch($people),
            'payrolls' => $this->payrolls($people),
            'payrollDocument' => $this->payrollDocument($people[0]),
            'employeeRows' => $this->employeeRows($people),
            'employeeCard' => $this->employeeCard($people[0]),
            'finance' => $this->finance(),
            'warehouse' => $this->warehouse(),
            'recruitment' => $this->recruitment(),
            'tasks' => $this->taskCards($people),
            'sprint' => $this->sprint(),
            'taskChartJson' => $this->taskChartJson(),
            'costChartJson' => $this->costChartJson(),
        ];
    }

    /**
     * @return list<SnapRecord>
     */
    private function people(): array
    {
        return [
            $this->person(101, 'Anna', 'Kowalska', 'Spawacz', '+48 600 101 101', 'anna.kowalska@demo.chronologic'),
            $this->person(102, 'Piotr', 'Nowak', 'Monter rurociągów', '+48 600 102 102', 'piotr.nowak@demo.chronologic'),
            $this->person(103, 'Marta', 'Wiśniewska', 'Dekarz', '+48 600 103 103', 'marta.wisniewska@demo.chronologic'),
            $this->person(104, 'Tomasz', 'Lewandowski', 'Ślusarz', '+48 600 104 104', 'tomasz.lewandowski@demo.chronologic'),
            $this->person(105, 'Karolina', 'Dąbrowska', 'Brygadzista', '+48 600 105 105', 'karolina.dabrowska@demo.chronologic'),
        ];
    }

    private function person(int $id, string $first, string $last, string $role, string $phone, string $email): SnapRecord
    {
        $roleRecord = new SnapRecord(['id' => $id, 'name' => $role]);

        return new SnapRecord([
            'id' => $id,
            'first_name' => $first,
            'last_name' => $last,
            'full_name' => $first.' '.$last,
            'phone' => $phone,
            'email' => $email,
            'image_path' => null,
            'image_url' => null,
            'roles' => collect([$roleRecord]),
            'isTerminated' => fn () => false,
        ]);
    }

    /**
     * @param  list<SnapRecord>  $people
     * @return array<string, mixed>
     */
    private function weekly(array $people, Carbon $weekStart): array
    {
        $location = new SnapRecord(['id' => 11, 'name' => 'Meyer Werft Papenburg']);
        $vehicle = new SnapRecord(['id' => 21, 'registration_number' => 'GDA 8K21', 'brand' => 'VW', 'model' => 'Transporter']);

        $mkEvent = function (int $id, Carbon $date, array $who, ?SnapRecord $to = null) use ($vehicle) {
            $participants = collect($who)->map(fn (SnapRecord $e) => new SnapRecord([
                'employee_id' => $e->id,
                'employee' => $e,
            ]));

            return new SnapRecord([
                'id' => $id,
                'event_date' => $date,
                'participants' => $participants,
                'toLocation' => $to,
                'vehicle' => $vehicle,
                'getVisualStatus' => fn () => 'oczekuje',
            ]);
        };

        $docs = collect([
            new SnapRecord([
                'id' => 31,
                'type' => 'Okresowy',
                'valid_to' => $this->today->copy()->addDays(12),
                'employee' => $people[0],
                'document' => new SnapRecord(['name' => 'Uprawnienia spawalnicze MAG']),
            ]),
        ]);

        return [
            'returnTrips' => collect([$mkEvent(41, $weekStart->copy()->addDays(4), [$people[3], $people[4]])]),
            'allDepartures' => collect([$mkEvent(42, $weekStart->copy()->addDays(1), [$people[0], $people[1], $people[2]], $location)]),
            'transferEvents' => collect([$mkEvent(43, $weekStart->copy()->addDays(2), [$people[1]])]),
            'employeesInFieldCount' => 4,
            'employeesInFieldByProject' => collect([
                (object) ['project_id' => 51, 'project_name' => 'Meyer Werft — blok 7', 'employee_count' => 3],
                (object) ['project_id' => 52, 'project_name' => 'HDW Kilonia — pokład', 'employee_count' => 2],
            ]),
            'expiringItems' => [
                'documents' => $docs,
                'accommodations' => collect([
                    new SnapRecord([
                        'id' => 61,
                        'name' => 'Papenburg — Am Deich 12',
                        'address' => 'Am Deich 12, Papenburg',
                        'lease_end_date' => $this->today->copy()->endOfMonth(),
                    ]),
                ]),
                'vehicle_inspections' => collect([
                    new SnapRecord([
                        'id' => 21,
                        'registration_number' => 'GDA 8K21',
                        'brand' => 'VW',
                        'model' => 'Transporter',
                        'inspection_valid_to' => $this->today->copy()->addDays(18),
                        'insurance_valid_to' => $this->today->copy()->addMonths(2),
                    ]),
                ]),
                'vehicle_insurance' => collect(),
            ],
            'projectsEndingThisMonth' => collect([
                new SnapRecord([
                    'id' => 52,
                    'name' => 'HDW Kilonia — pokład',
                    'end_date' => $this->today->copy()->endOfMonth()->subDays(3),
                ]),
            ]),
            'projectCard' => [
                'name' => 'Meyer Werft — blok 7',
                'location' => 'Papenburg',
                'demand' => [
                    ['role' => 'Spawacz', 'need' => 2, 'have' => 2],
                    ['role' => 'Monter rurociągów', 'need' => 1, 'have' => 1],
                    ['role' => 'Dekarz', 'need' => 1, 'have' => 0],
                ],
                'assigned' => [$people[0], $people[1], $people[4]],
            ],
        ];
    }

    /**
     * @param  list<SnapRecord>  $people
     * @return array<string, mixed>
     */
    private function timeLogs(array $people): array
    {
        $monthStart = $this->today->copy()->startOfMonth();
        $monthEnd = $this->today->copy()->endOfMonth();
        $days = [];
        $cursor = $monthStart->copy();
        while ($cursor->lte($monthEnd)) {
            $days[] = [
                'number' => (int) $cursor->format('j'),
                'date' => $cursor->copy(),
                'isWeekend' => $cursor->isWeekend(),
            ];
            $cursor->addDay();
        }

        $location = new SnapRecord(['name' => 'Papenburg']);
        $meyer = new SnapRecord(['id' => 51, 'name' => 'Meyer Werft — blok 7', 'location' => $location]);
        $hdw = new SnapRecord(['id' => 52, 'name' => 'HDW Kilonia — pokład', 'location' => new SnapRecord(['name' => 'Kilonia'])]);

        $assign = function (SnapRecord $employee, int $fromDay, int $toDay, array $hoursMap) use ($days) {
            $daysInAssignment = [];
            $timeLogs = [];
            foreach ($days as $day) {
                $n = $day['number'];
                if ($n >= $fromDay && $n <= $toDay && ! $day['isWeekend']) {
                    $daysInAssignment[$n] = true;
                    if (isset($hoursMap[$n])) {
                        $timeLogs[$n] = ['hours' => $hoursMap[$n]];
                    } elseif ($n < $this->today->day) {
                        $timeLogs[$n] = ['hours' => 8];
                    }
                }
            }

            return [
                'employee' => $employee,
                'timeLogs' => $timeLogs,
                'daysInAssignment' => $daysInAssignment,
            ];
        };

        return [
            'currentDate' => $this->today->copy()->startOfMonth(),
            'monthStart' => $monthStart,
            'monthEnd' => $monthEnd,
            'days' => $days,
            'projectsData' => [
                [
                    'project' => $meyer,
                    'assignments' => [
                        $assign($people[0], 1, 20, [1 => 8, 2 => 8.5, 3 => 8, 4 => 7.5, 5 => 8]),
                        $assign($people[1], 1, 28, [1 => 8, 2 => 8, 3 => 9]),
                    ],
                ],
                [
                    'project' => $hdw,
                    'assignments' => [
                        $assign($people[2], 8, 30, [8 => 8, 9 => 8, 10 => 6]),
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  list<SnapRecord>  $people
     * @return list<array<string, mixed>>
     */
    private function payrollBatch(array $people): array
    {
        $rows = [
            [168, 18.50, 3108.00],
            [176, 19.00, 3344.00],
            [152, 17.50, 2660.00],
            [160, 18.00, 2880.00],
            [184, 22.00, 4048.00],
        ];

        $out = [];
        foreach ($people as $i => $person) {
            $out[] = [
                'employee' => [
                    'id' => $person->id,
                    'full_name' => $person->full_name,
                    'roles' => [['name' => $person->roles->first()->name]],
                ],
                'hours' => $rows[$i][0],
                'rate' => $rows[$i][1],
                'amount' => $rows[$i][2],
                'currency' => 'EUR',
            ];
        }

        return $out;
    }

    /**
     * @param  list<SnapRecord>  $people
     * @return Collection<int, SnapRecord>
     */
    private function payrolls(array $people): Collection
    {
        $start = $this->today->copy()->subMonth()->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $statuses = [PayrollStatus::PAID, PayrollStatus::APPROVED, PayrollStatus::ISSUED, PayrollStatus::DRAFT, PayrollStatus::DRAFT];
        $amounts = [3108.00, 3344.00, 2660.00, 2880.00, 4048.00];
        $hoursAmt = [3108.00, 3344.00, 2660.00, 2880.00, 3848.00];
        $adj = [0.0, 0.0, 0.0, 0.0, 200.0];

        return collect($people)->values()->map(function (SnapRecord $person, int $i) use ($start, $end, $statuses, $amounts, $hoursAmt, $adj) {
            $status = $statuses[$i];
            $payout = $amounts[$i];

            return new SnapRecord([
                'id' => 700 + $i,
                'employee' => $person,
                'period_start' => $start,
                'period_end' => $end,
                'currency' => 'EUR',
                'hours_amount' => $hoursAmt[$i],
                'adjustments_amount' => $adj[$i],
                'total_amount' => $payout,
                'status' => $status,
                'rate_summary' => ['type' => 'single', 'amount' => round($hoursAmt[$i] / 160, 2)],
                'correction_totals_by_currency' => $adj[$i] != 0.0 ? ['EUR' => $adj[$i]] : [],
                'payout_totals_by_currency' => ['EUR' => $payout],
                'correctionTotalsByCurrency' => fn () => $adj[$i] != 0.0 ? ['EUR' => $adj[$i]] : [],
                'payoutTotalsByCurrency' => fn () => ['EUR' => $payout],
            ]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function payrollDocument(SnapRecord $person): array
    {
        $start = $this->today->copy()->subMonth()->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $hoursBreakdown = [];
        $cursor = $start->copy();
        $n = 0;
        while ($cursor->lte($end) && $n < 8) {
            if (! $cursor->isWeekend()) {
                $hoursBreakdown[] = [
                    'date' => $cursor->toDateString(),
                    'project' => 'Meyer Werft — blok 7',
                    'hours' => 8.0,
                    'rate' => 18.50,
                    'rate_currency' => 'EUR',
                    'amount' => 148.00,
                ];
                $n++;
            }
            $cursor->addDay();
        }

        $payroll = new SnapRecord([
            'id' => 700,
            'employee' => $person,
            'period_start' => $start,
            'period_end' => $end,
            'currency' => 'EUR',
            'hours_amount' => 1184.00,
            'status' => PayrollStatus::APPROVED,
            'created_at' => $end->copy()->addDays(2),
            'adjustments' => collect(),
            'advances' => collect(),
            'correctionTotalsByCurrency' => fn () => [],
            'payoutTotalsByCurrency' => fn () => ['EUR' => 1184.00],
        ]);

        return [
            'payroll' => $payroll,
            'hoursBreakdown' => $hoursBreakdown,
        ];
    }

    /**
     * @param  list<SnapRecord>  $people
     * @return list<array<string, mixed>>
     */
    private function employeeRows(array $people): array
    {
        $states = [
            EmployeeLocationState::OUTSIDE_BASE,
            EmployeeLocationState::OUTSIDE_BASE,
            EmployeeLocationState::IN_BASE,
            EmployeeLocationState::IN_TRANSIT,
            EmployeeLocationState::OUTSIDE_BASE,
        ];

        $rows = [];
        foreach ($people as $i => $person) {
            $state = $states[$i];
            $inField = $state === EmployeeLocationState::OUTSIDE_BASE;
            $rows[] = [
                'employee' => $person,
                'hasActiveRotation' => $i !== 2,
                'company' => 'ChronoLogic Sp. z o.o.',
                'locationStatus' => [
                    'state' => $state,
                    'accommodation_names' => $inField ? ['Papenburg — Am Deich 12'] : [],
                    'vehicle_labels' => $inField || $state === EmployeeLocationState::IN_TRANSIT ? ['GDA 8K21'] : [],
                    'project_names' => $inField ? ($i === 2 ? [] : ['Meyer Werft — blok 7']) : [],
                    'has_assignment_overlap' => false,
                ],
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function employeeCard(SnapRecord $person): array
    {
        return [
            'employee' => $person,
            'documents' => [
                ['name' => 'Uprawnienia MAG', 'until' => $this->today->copy()->addDays(12), 'ok' => true],
                ['name' => 'Badania lekarskie', 'until' => $this->today->copy()->addMonths(4), 'ok' => true],
                ['name' => 'BHP — praca na wysokości', 'until' => $this->today->copy()->subDays(5), 'ok' => false],
            ],
            'rotation' => [
                'start' => $this->today->copy()->subWeeks(3),
                'end' => $this->today->copy()->addWeeks(3),
                'status' => 'Aktywna',
            ],
            'assignment' => [
                'project' => 'Meyer Werft — blok 7',
                'role' => 'Spawacz',
                'from' => $this->today->copy()->startOfMonth(),
                'to' => $this->today->copy()->addDays(18),
            ],
            'home' => 'Papenburg — Am Deich 12',
            'car' => 'GDA 8K21 · VW Transporter',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function finance(): array
    {
        return [
            'label' => $this->today->copy()->locale('pl')->translatedFormat('F Y'),
            'kpis' => [
                'projects' => 6,
                'avg_margin' => 18.4,
                'plan' => 92.1,
            ],
            'summary' => [
                'revenue' => 184200,
                'labor' => 96200,
                'variable' => 21400,
                'margin' => 66600,
            ],
            'projects' => [
                ['name' => 'Meyer Werft — blok 7', 'revenue' => 84200, 'margin' => 24.1, 'hours' => 1240],
                ['name' => 'HDW Kilonia — pokład', 'revenue' => 51800, 'margin' => 16.8, 'hours' => 880],
                ['name' => 'Fincantieri — sekcja A', 'revenue' => 48200, 'margin' => 11.2, 'hours' => 640],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function warehouse(): array
    {
        $hq = new SnapRecord(['id' => 1, 'name' => 'Siedziba Gdańsk', 'is_default' => true, 'location' => new SnapRecord(['name' => 'Gdańsk'])]);
        $field = new SnapRecord(['id' => 2, 'name' => 'Papenburg', 'is_default' => false, 'location' => new SnapRecord(['name' => 'Papenburg'])]);

        $item = function (int $id, string $name, string $category, int $stock, int $reserved, int $others, int $issued) {
            return new SnapRecord([
                'id' => $id,
                'name' => $name,
                'category' => $category,
                'image_url' => null,
                'variant_label' => null,
                'variants' => collect(),
                'hasVariants' => fn () => false,
                'isArchived' => fn () => false,
                'quantityIn' => fn () => $stock,
                'reservedIn' => fn () => $reserved,
                'quantityInOthers' => fn () => $others,
                'issuedOutstandingIn' => fn () => $issued,
                'issuedOutstandingInOthers' => fn () => 0,
            ]);
        };

        return [
            'warehouses' => collect([$hq, $field]),
            'current' => $hq,
            'counts' => collect([1 => 4, 2 => 3]),
            'items' => collect([
                $item(1, 'Kask ochronny', 'BHP', 42, 6, 8, 14),
                $item(2, 'Buty S3', 'BHP', 18, 2, 4, 9),
                $item(3, 'Uchwyt TIG', 'Narzędzia', 7, 1, 2, 3),
                $item(4, 'Kamera spawalnicza', 'Elektronika', 2, 1, 0, 1),
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function recruitment(): array
    {
        $mk = function (int $id, string $first, string $last, string $phone, RecruitmentStatus $status, ?RecruitmentCandidateFlag $flag = null) {
            $proc = new SnapRecord([
                'id' => 800 + $id,
                'status' => $status,
            ]);

            return new SnapRecord([
                'id' => $id,
                'first_name' => $first,
                'last_name' => $last,
                'full_name' => $first.' '.$last,
                'phone' => $phone,
                'email' => null,
                'photo_url' => null,
                'rating' => $flag,
                'processes' => collect([$proc]),
            ]);
        };

        $candidates = collect([
            $mk(1, 'Jakub', 'Zieliński', '+48 512 200 011', RecruitmentStatus::Nowy, RecruitmentCandidateFlag::Wartosciowy),
            $mk(2, 'Oliwia', 'Król', '+48 512 200 012', RecruitmentStatus::WTrakcieKontaktu),
            $mk(3, 'Paweł', 'Wójcik', '+48 512 200 013', RecruitmentStatus::Zaakceptowany),
            $mk(4, 'Natalia', 'Mazur', '+48 512 200 014', RecruitmentStatus::Onboarding),
        ]);

        return [
            'candidates' => $candidates,
            'process' => [
                'candidate' => $candidates[2],
                'status' => RecruitmentStatus::Zaakceptowany,
                'role' => 'Spawacz MAG',
                'source' => 'Polecenie',
                'next' => 'Weryfikacja dokumentów + rozmowa z brygadzistą',
            ],
            'pipeline' => RecruitmentStatus::pipelineSteps(),
        ];
    }

    /**
     * @param  list<SnapRecord>  $people
     * @return list<array<string, mixed>>
     */
    private function taskCards(array $people): array
    {
        return [
            ['id' => 12, 'name' => 'Dowieźć kaski na blok 7', 'status' => TaskStatus::IN_PROGRESS, 'assignee' => $people[4]->full_name, 'priority' => 4, 'category' => 'Logistyka'],
            ['id' => 18, 'name' => 'Rozliczyć godziny sierpnia — Meyer', 'status' => TaskStatus::PENDING, 'assignee' => 'Kasia HR', 'priority' => 5, 'category' => 'Finanse'],
            ['id' => 21, 'name' => 'Przedłużyć najem Am Deich', 'status' => TaskStatus::IN_PROGRESS, 'assignee' => 'Adam', 'priority' => 4, 'category' => 'Zakwaterowanie'],
            ['id' => 27, 'name' => 'Onboarding Natalia Mazur', 'status' => TaskStatus::PENDING, 'assignee' => 'Kasia HR', 'priority' => 3, 'category' => 'Rekrutacja'],
            ['id' => 9, 'name' => 'Przegląd GDA 8K21', 'status' => TaskStatus::COMPLETED, 'assignee' => $people[3]->full_name, 'priority' => 3, 'category' => 'Flota'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sprint(): array
    {
        $start = $this->today->copy()->startOfWeek();
        $end = $start->copy()->addDays(13);
        $progress = 62;
        $ring = 2 * pi() * 42;

        return [
            'name' => 'Sprint 14 — logistyka + payroll',
            'goal' => 'Wyjazdy V2 na produkcji i listy płac bez ręcznego Excelu.',
            'start' => $start,
            'end' => $end,
            'progress' => $progress,
            'ring' => $ring,
            'dash' => $ring * (1 - $progress / 100),
            'health' => 'Na kursie',
            'days_left' => 6,
            'days_elapsed' => 8,
            'days_total' => 14,
            'done' => 8,
            'scope' => 13,
            'in_progress' => 3,
            'overdue' => 1,
            'milestones_done' => 1,
            'milestones_total' => 3,
            'coach' => 'Zakres trzyma się linii. Jeden item po terminie — przegląd auta.',
            'tasks' => [
                ['pos' => 1, 'name' => 'Dowieźć kaski na blok 7', 'who' => 'Karolina Dąbrowska', 'done' => false],
                ['pos' => 2, 'name' => 'Rozliczyć godziny sierpnia', 'who' => 'Kasia HR', 'done' => false],
                ['pos' => 3, 'name' => 'Onboarding Natalia Mazur', 'who' => 'Kasia HR', 'done' => false],
                ['pos' => 4, 'name' => 'Przegląd GDA 8K21', 'who' => 'Tomasz Lewandowski', 'done' => true],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function taskChartJson(): array
    {
        $start = $this->today->copy()->subWeeks(4);
        $names = ['Anna Kowalska', 'Piotr Nowak', 'Karolina Dąbrowska', 'Kasia HR', 'Adam'];
        $tasks = [];
        $id = 1;
        $statuses = ['completed', 'completed', 'completed', 'in_progress', 'pending', 'cancelled'];
        $categories = ['Logistyka', 'Finanse', 'Rekrutacja', 'Flota', 'Zakwaterowanie'];

        for ($i = 0; $i < 18; $i++) {
            $created = $start->copy()->addDays($i);
            $status = $statuses[$i % count($statuses)];
            $assignee = $names[$i % count($names)];
            $creator = $names[($i + 2) % count($names)];
            $comments = [];
            if ($i % 3 === 0) {
                $comments[] = [
                    'author' => ['name' => $creator],
                    'created_at' => $created->copy()->addHours(4)->toIso8601String(),
                    'updated_at' => $created->copy()->addHours(4)->toIso8601String(),
                    'mentions' => [['resolved_user' => ['name' => $assignee]]],
                ];
            }
            $tasks[] = [
                'id' => $id++,
                'name' => $categories[$i % count($categories)].' · item '.$id,
                'status' => $status,
                'category' => $categories[$i % count($categories)],
                'created_at' => $created->toIso8601String(),
                'updated_at' => $created->copy()->addDays($status === 'completed' ? 2 : 1)->toIso8601String(),
                'completed_at' => $status === 'completed' ? $created->copy()->addDays(2)->toIso8601String() : null,
                'assigned_to' => ['name' => $assignee],
                'created_by' => ['name' => $creator],
                'comments' => $comments,
                'subtasks' => $i % 2 === 0 ? [['is_completed' => true], ['is_completed' => false]] : [],
            ];
        }

        return [
            'meta' => [
                'period' => [
                    'start_date' => $start->toDateString(),
                    'end_date' => $this->today->toDateString(),
                ],
            ],
            'tasks' => $tasks,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function costChartJson(): array
    {
        $start = $this->today->copy()->startOfMonth();

        return [
            'meta' => [
                'period' => [
                    'start_date' => $start->toDateString(),
                    'end_date' => $this->today->copy()->endOfMonth()->toDateString(),
                ],
            ],
            'summary' => [
                'by_currency_and_type' => [
                    'EUR' => [
                        'fixed' => 12400,
                        'variable' => 8600,
                        'transport' => 4100,
                        'accommodation' => 9800,
                        'labor' => 96200,
                        'vehicle_repairs' => 1400,
                        'total' => 132500,
                    ],
                    'PLN' => [
                        'fixed' => 8200,
                        'variable' => 2100,
                        'transport' => 0,
                        'accommodation' => 0,
                        'labor' => 0,
                        'vehicle_repairs' => 600,
                        'total' => 10900,
                    ],
                ],
            ],
        ];
    }
}
