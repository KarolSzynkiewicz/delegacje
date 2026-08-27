<?php

namespace App\Support;

/**
 * Persony AskChrono — każdy wariant bota ma imię i specjalizację UI.
 */
final class ChronoPersona
{
    public const CLOCK = 'clock';

    public const VISOR = 'visor';

    public const ORB = 'orb';

    public const SPARK = 'spark';

    /**
     * @return array<string, array{
     *     variant: string,
     *     name: string,
     *     role: string,
     *     tagline: string,
     *     specialty: string,
     *     actions: list<string>,
     *     blurb: string
     * }>
     */
    public static function all(): array
    {
        return [
            self::CLOCK => [
                'variant' => self::CLOCK,
                'name' => 'Chrono',
                'role' => 'Twórca',
                'tagline' => 'Zaczynam od zera',
                'specialty' => 'create',
                'actions' => ['create'],
                'blurb' => 'Maskotka zespołu. Układa nowe zadania, sprinty i procedury z kontekstu, w którym stoicie.',
            ],
            self::VISOR => [
                'variant' => self::VISOR,
                'name' => 'Wizjer',
                'role' => 'Analityk',
                'tagline' => 'Widzę wzorce w filtrze',
                'specialty' => 'summarize',
                'actions' => ['summarize'],
                'blurb' => 'Czyta backlog jak raport. Briefy, standup i ryzyka — bez wymyślania zadań spoza widoku.',
            ],
            self::ORB => [
                'variant' => self::ORB,
                'name' => 'Orbi',
                'role' => 'Kurier',
                'tagline' => 'Wnoszę i wynoszę dane',
                'specialty' => 'transfer',
                'actions' => ['transfer', 'import', 'export'],
                'blurb' => 'Import z JSON / listy i eksport bieżącego filtra. Kompaktowy — mieści się nawet w wąskim panelu.',
            ],
            self::SPARK => [
                'variant' => self::SPARK,
                'name' => 'Iskra',
                'role' => 'Redaktor',
                'tagline' => 'Dopieszczam to, co już jest',
                'specialty' => 'mutate',
                'actions' => ['mutate'],
                'blurb' => 'Precyzyjne mutacje: kategorie, przypisania, doprecyzowanie opisów. Bez zbędnej twarzy — sam znak.',
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    public static function get(string $variant): ?array
    {
        return self::all()[$variant] ?? null;
    }

    public static function name(string $variant): string
    {
        return self::get($variant)['name'] ?? $variant;
    }

    /** Wariant bota dla klucza akcji Assist (summarize, import, …). */
    public static function forAction(string $actionKey): string
    {
        foreach (self::all() as $persona) {
            if (in_array($actionKey, $persona['actions'], true)) {
                return $persona['variant'];
            }
        }

        return self::CLOCK;
    }

    /** @return list<string> */
    public static function variants(): array
    {
        return array_keys(self::all());
    }
}
