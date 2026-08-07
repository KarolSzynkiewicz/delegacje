<?php

namespace App\Support;

use App\Enums\RecruitmentCandidateFlag;
use App\Enums\RecruitmentContactOutcome;
use App\Enums\RecruitmentStatus;
use App\Enums\TaskStatus;

/**
 * Definicje wierszy backlogu — „co jeszcze czeka na działanie”.
 *
 * Część warunków (brak udanej rozmowy, mniej niż 3 próby, otwarte zadanie) nie da
 * się wyrazić samym statusem, dlatego lista procesów przyjmuje je jako ?backlog=…
 * Liczniki w analityce i filtr listy przechodzą przez ten sam kod, więc liczba w
 * tabeli zawsze opisuje dokładnie tę listę, do której linkuje.
 *
 * Wszystkie warunki są „stanem na teraz” — backlog to zaległość, nie zdarzenie
 * z wybranego okresu.
 */
final class RecruitmentBacklog
{
    public const NOWE = 'nowe';

    public const DO_ZADZWONIENIA = 'do_zadzwonienia';

    public const PONIZEJ_3_PROB = 'ponizej_3_prob';

    public const ODDZWON = 'oddzwon';

    public const WERYFIKACJA = 'weryfikacja';

    public const ONBOARDING = 'onboarding';

    public const WARTOSCIOWY = 'wartosciowy';

    /** Ile prób kontaktu uznajemy za „kandydat dostał uczciwą szansę”. */
    public const FAIR_ATTEMPTS = 3;

    /** Statusy, w których proces jest jeszcze otwarty. */
    public const OPEN_STATUSES = [
        RecruitmentStatus::Nowy,
        RecruitmentStatus::WTrakcieKontaktu,
        RecruitmentStatus::Zaakceptowany,
        RecruitmentStatus::Onboarding,
    ];

    /**
     * Wiersze tabeli backlogu w kolejności czytania. `params` to komplet parametrów
     * GET, którymi lista procesów odtworzy dokładnie ten sam zbiór.
     *
     * @return array<string, array{label: string, short: string, hint: string, params: array<string, string>}>
     */
    public static function rows(): array
    {
        return [
            self::NOWE => [
                'label' => 'Nowe',
                'short' => 'Nowe',
                'hint' => 'Status „Nowy” — lead wpadł i nikt jeszcze nie ruszył procesu.',
                'params' => ['status' => RecruitmentStatus::Nowy->value],
            ],
            self::DO_ZADZWONIENIA => [
                'label' => 'W kontakcie bez udanej rozmowy — dziś nie dzwoniliśmy',
                'short' => 'Do zadzwonienia dziś',
                'hint' => 'Status „W trakcie kontaktu”, żadna próba nie skończyła się „Odebrał(a)” i dziś nie było próby. To lista na dziś do telefonu.',
                'params' => ['backlog' => self::DO_ZADZWONIENIA],
            ],
            self::PONIZEJ_3_PROB => [
                'label' => 'Mniej niż '.self::FAIR_ATTEMPTS.' próby kontaktu',
                'short' => '< '.self::FAIR_ATTEMPTS.' próby',
                'hint' => 'Otwarty proces (nowy albo w trakcie kontaktu) z 0–'.(self::FAIR_ATTEMPTS - 1).' zarejestrowanymi próbami — kandydat nie dostał jeszcze uczciwej szansy.',
                'params' => ['backlog' => self::PONIZEJ_3_PROB],
            ],
            self::ODDZWON => [
                'label' => 'W kontakcie z zadaniem „oddzwoń”',
                'short' => 'Zadanie oddzwoń',
                'hint' => 'Status „W trakcie kontaktu” z otwartym zadaniem przypiętym do procesu — ktoś obiecał wrócić z telefonem.',
                'params' => ['backlog' => self::ODDZWON],
            ],
            self::WERYFIKACJA => [
                'label' => 'Weryfikacja',
                'short' => 'Weryfikacja',
                'hint' => 'Status „Weryfikacja” — czekają na sprawdzenie dokumentów i decyzję.',
                'params' => ['status' => RecruitmentStatus::Zaakceptowany->value],
            ],
            self::ONBOARDING => [
                'label' => 'Onboarding',
                'short' => 'Onboarding',
                'hint' => 'Status „Onboarding” — decyzja jest, trwa wdrożenie.',
                'params' => ['status' => RecruitmentStatus::Onboarding->value],
            ],
            self::WARTOSCIOWY => [
                'label' => 'Wartościowy kandydat',
                'short' => 'Wartościowy',
                'hint' => 'Kandydat oznaczony jako wartościowy, którego proces jest jeszcze otwarty — najdroższy zasób do zgubienia.',
                'params' => ['backlog' => self::WARTOSCIOWY],
            ],
        ];
    }

    /**
     * Wartości przyjmowane w ?backlog= na liście procesów. Wiersze opisane samym
     * statusem tu nie trafiają — tam wystarczy ?status=.
     *
     * @return list<string>
     */
    public static function filterKeys(): array
    {
        return [self::DO_ZADZWONIENIA, self::PONIZEJ_3_PROB, self::ODDZWON, self::WARTOSCIOWY];
    }

    public static function sanitizeFilterKey(string $key): string
    {
        return in_array($key, self::filterKeys(), true) ? $key : '';
    }

    public static function label(string $key): ?string
    {
        return self::rows()[$key]['label'] ?? null;
    }

    /**
     * Dokłada warunek wiersza do zapytania po procesach rekrutacyjnych.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\RecruitmentProcess>|\Illuminate\Database\Query\Builder  $query
     * @param  string  $alias  alias, pod jakim procesy występują w tym zapytaniu
     */
    public static function constrain($query, string $key, string $alias = 'recruitment_processes'): void
    {
        match ($key) {
            self::NOWE => $query->where($alias.'.status', RecruitmentStatus::Nowy->value),
            self::WERYFIKACJA => $query->where($alias.'.status', RecruitmentStatus::Zaakceptowany->value),
            self::ONBOARDING => $query->where($alias.'.status', RecruitmentStatus::Onboarding->value),
            self::DO_ZADZWONIENIA => self::constrainToCall($query, $alias),
            self::PONIZEJ_3_PROB => self::constrainUnderFairAttempts($query, $alias),
            self::ODDZWON => self::constrainCallbackTask($query, $alias),
            self::WARTOSCIOWY => self::constrainValuable($query, $alias),
            default => null,
        };
    }

    private static function constrainToCall($query, string $alias): void
    {
        $query->where($alias.'.status', RecruitmentStatus::WTrakcieKontaktu->value)
            ->whereRaw(
                'NOT EXISTS (SELECT 1 FROM recruitment_contact_attempts bca'
                .' WHERE bca.recruitment_process_id = '.$alias.'.id AND bca.outcome = ?)',
                [RecruitmentContactOutcome::Odebrano->value]
            )
            ->whereRaw(
                'NOT EXISTS (SELECT 1 FROM recruitment_contact_attempts bcb'
                .' WHERE bcb.recruitment_process_id = '.$alias.'.id AND bcb.created_at >= ?)',
                [now()->startOfDay()->toDateTimeString()]
            );
    }

    private static function constrainUnderFairAttempts($query, string $alias): void
    {
        $query->whereIn($alias.'.status', [
            RecruitmentStatus::Nowy->value,
            RecruitmentStatus::WTrakcieKontaktu->value,
        ])->whereRaw(
            '(SELECT COUNT(*) FROM recruitment_contact_attempts bcc'
            .' WHERE bcc.recruitment_process_id = '.$alias.'.id) < ?',
            [self::FAIR_ATTEMPTS]
        );
    }

    private static function constrainCallbackTask($query, string $alias): void
    {
        $query->where($alias.'.status', RecruitmentStatus::WTrakcieKontaktu->value)
            ->whereRaw(
                'EXISTS (SELECT 1 FROM project_tasks bpt'
                .' WHERE bpt.recruitment_process_id = '.$alias.'.id AND bpt.status NOT IN (?, ?))',
                [TaskStatus::COMPLETED->value, TaskStatus::CANCELLED->value]
            );
    }

    private static function constrainValuable($query, string $alias): void
    {
        $query->whereIn(
            $alias.'.status',
            array_map(fn (RecruitmentStatus $s) => $s->value, self::OPEN_STATUSES)
        )->whereRaw(
            'EXISTS (SELECT 1 FROM recruitment_candidates brc'
            .' WHERE brc.id = '.$alias.'.candidate_id AND brc.rating = ?)',
            [RecruitmentCandidateFlag::Wartosciowy->value]
        );
    }
}
