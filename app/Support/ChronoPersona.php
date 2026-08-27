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

    /** Kandydaci domenowi (galeria /2) — jeszcze nie w rosterze Assist. */
    public const LENS = 'lens';

    public const FERRY = 'ferry';

    public const NIB = 'nib';

    public const FORGE = 'forge';

    public const RADAR = 'radar';

    /**
     * @return array<string, array{
     *     variant: string,
     *     name: string,
     *     role: string,
     *     tagline: string,
     *     specialty: string,
     *     actions: list<string>,
     *     blurb: string,
     *     pitch: string,
     *     can: list<string>
     * }>
     */
    public static function all(): array
    {
        return [
            self::FORGE => [
                'variant' => self::FORGE,
                'name' => 'Chrono',
                'role' => 'Twórca',
                'tagline' => 'Zaczynam od zera',
                'specialty' => 'create',
                'actions' => ['create'],
                'blurb' => 'Plus, blueprint i klocki w dłoni = buduję nowe. Zadania, sprinty, procedury z kontekstu.',
                'pitch' => 'Zgarnia kontekst i proponuje następne kroki. Mistrz planowania, wskazywania kierunku i zagrożeń.',
                'can' => [
                    'Następne kroki z kontekstu',
                    'Kierunek i zagrożenia',
                    'Propozycje przed zapisem',
                ],
            ],
            self::LENS => [
                'variant' => self::LENS,
                'name' => 'Argus',
                'role' => 'Analityk',
                'tagline' => 'Widzę wzorce w filtrze',
                'specialty' => 'summarize',
                'actions' => ['summarize'],
                'blurb' => 'Oko + HUD, a myśląc świeci wiązką w dół jak radar. Brief, standup, ryzyka.',
                'pitch' => 'Widzi wszystko, co dzieje się w Twojej firmie. Dzienne podsumowania, raporty kontekstowe, blokery, ryzyka i szanse — co idzie dobrze, a co źle.',
                'can' => [
                    'Dzienne podsumowania',
                    'Blokery, ryzyka i szanse',
                    'Raporty kontekstowe',
                ],
            ],
            self::ORB => [
                'variant' => self::ORB,
                'name' => 'Impek',
                'role' => 'Kurier',
                'tagline' => 'Wnoszę i wynoszę dane',
                'specialty' => 'transfer',
                'actions' => ['transfer', 'import', 'export'],
                'blurb' => 'Strzałki ↓↑ i paczka w dłoni = wozi dane. Eksportuje filtr, wwozi nowe rekordy i listy. Nie edytuje tego, co już jest.',
                'pitch' => 'Zgarnia kontekst i wozi dane zbiorowo — import i eksport wielu rekordów do analizy, systemów zewnętrznych, JSON i CSV.',
                'can' => [
                    'Import zbiorczy',
                    'Eksport JSON / CSV',
                    'Wymiana z systemami zewnętrznymi',
                ],
            ],
            self::VISOR => [
                'variant' => self::VISOR,
                'name' => 'Edi',
                'role' => 'Redaktor',
                'tagline' => 'Dopieszczam to, co już jest',
                'specialty' => 'mutate',
                'actions' => ['mutate'],
                'blurb' => 'Kartka z tekstem w lewej dłoni, wykres w prawej — czyta eksport i proponuje poprawki pól. Nie dodaje ani nie kasuje rekordów.',
                'pitch' => 'Mistrz redagowania. Uzupełnia brakujące dane z kontekstu — zawsze jako propozycje do Twojej decyzji.',
                'can' => [
                    'Uzupełnianie braków',
                    'Propozycje zmian z kontekstu',
                    'Zatwierdzasz przed zapisem',
                ],
            ],
        ];
    }

    /**
     * Dodatkowe warianty SVG do wyboru domenowego looku (nie w rosterze Assist).
     *
     * @return array<string, array{variant: string, name: string, role: string, tagline: string, specialty: string, blurb: string, replaces?: string}>
     */
    public static function candidates(): array
    {
        return [
            self::CLOCK => [
                'variant' => self::CLOCK,
                'name' => 'Zegar',
                'role' => 'Twórca',
                'tagline' => 'Klasyczna tarcza',
                'specialty' => 'create',
                'blurb' => 'Alt dla Chrono: sama tarcza i klocek z plusem, bez blueprintu w tle.',
                'replaces' => self::FORGE,
            ],
            self::RADAR => [
                'variant' => self::RADAR,
                'name' => 'Radar',
                'role' => 'Analityk',
                'tagline' => 'Skanuję backlog',
                'specialty' => 'summarize',
                'blurb' => 'Alt dla Argusa: czasza radaru i blipy na klatce — „wykrywam ryzyka”.',
                'replaces' => self::LENS,
            ],
            self::FERRY => [
                'variant' => self::FERRY,
                'name' => 'Prom',
                'role' => 'Kurier',
                'tagline' => 'Wwożę i wywożę',
                'specialty' => 'transfer',
                'blurb' => 'Alt dla Impka: kapsuła + strzałki ↓↑ — od razu widać import/export.',
                'replaces' => self::ORB,
            ],
            self::NIB => [
                'variant' => self::NIB,
                'name' => 'Pióro',
                'role' => 'Redaktor',
                'tagline' => 'Dopisuję i poprawiam',
                'specialty' => 'mutate',
                'blurb' => 'Alt dla Edi: stalówka + linie tekstu i kursor — domena edycji.',
                'replaces' => self::VISOR,
            ],
            self::SPARK => [
                'variant' => self::SPARK,
                'name' => 'Iskra',
                'role' => 'Zegar',
                'tagline' => 'Licznik myśli',
                'specialty' => 'loading',
                'blurb' => 'Czysty znak zegara — loading w contentcie podczas myślenia Chrono. Bez atrybutu, bez twarzy.',
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    public static function get(string $variant): ?array
    {
        return self::all()[$variant] ?? self::candidates()[$variant] ?? null;
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
