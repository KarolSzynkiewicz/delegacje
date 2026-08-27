<?php

namespace App\Support;

/**
 * Drzewo akcji Chrono Assist — jeden katalog dla playgroundu i Livewire.
 */
final class ChronoAssistCatalog
{
    /**
     * Kontekst widoku — które liście są naprawdę obsłużone.
     */
    public const CONTEXT_GRID = 'grid';

    public const CONTEXT_TASK = 'task';

    /**
     * @return list<array<string, mixed>>
     */
    public static function actions(): array
    {
        return [
            [
                'key' => 'summarize',
                'persona' => ChronoPersona::LENS,
                'label' => 'Podsumuj',
                'hint' => 'Brief, standup, ryzyka',
                'children' => [
                    ['key' => 'summary-brief', 'label' => 'Krótki brief', 'hint' => '3–5 zdań + ryzyka', 'icon' => 'bi-lightning'],
                    ['key' => 'summary-standup', 'label' => 'Standup', 'hint' => 'Punkty do omówienia dziś', 'icon' => 'bi-people'],
                    ['key' => 'summary-risks', 'label' => 'Same ryzyka', 'hint' => 'Przeterminowane, blokery', 'icon' => 'bi-exclamation-triangle'],
                ],
            ],
            [
                'key' => 'transfer',
                'persona' => ChronoPersona::ORB,
                'label' => 'Import / Export',
                'hint' => 'Wnoszę i wynoszę dane',
                'children' => [
                    [
                        'key' => 'import',
                        'label' => 'Importuj',
                        'hint' => 'JSON albo lista linii',
                        'icon' => 'bi-box-arrow-in-down',
                        'children' => [
                            ['key' => 'import-json', 'label' => 'Wklej JSON', 'hint' => 'Tworzy nowe rekordy', 'icon' => 'bi-braces'],
                            ['key' => 'import-list', 'label' => 'Lista linii', 'hint' => '@osoba i // opis', 'icon' => 'bi-list-ul'],
                        ],
                    ],
                    [
                        'key' => 'export',
                        'label' => 'Eksportuj',
                        'hint' => 'Bieżący filtr',
                        'icon' => 'bi-box-arrow-up',
                        'children' => [
                            ['key' => 'export-json', 'label' => 'JSON', 'hint' => 'Stan filtra dla Ediego', 'icon' => 'bi-filetype-json'],
                            ['key' => 'export-csv', 'label' => 'CSV', 'hint' => 'Do arkusza', 'icon' => 'bi-filetype-csv'],
                            ['key' => 'export-md', 'label' => 'Markdown', 'hint' => 'Do notatki / PR', 'icon' => 'bi-markdown'],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'create',
                'persona' => ChronoPersona::FORGE,
                'label' => 'Twórz',
                'hint' => 'Task, sprint, SOP',
                'children' => [
                    ['key' => 'create-task', 'label' => 'Zadanie + podzadania', 'hint' => 'Z kontekstu filtra', 'icon' => 'bi-check2-square'],
                    ['key' => 'create-sprint', 'label' => 'Sprint', 'hint' => 'Cel, DoD, scope', 'icon' => 'bi-flag'],
                    ['key' => 'create-procedure', 'label' => 'Procedura', 'hint' => 'Flow z kroków', 'icon' => 'bi-diagram-3'],
                ],
            ],
            [
                'key' => 'mutate',
                'persona' => ChronoPersona::VISOR,
                'label' => 'Mutuj',
                'hint' => 'Edi przyjmuje treść, oddaje poprawki',
                'children' => [
                    ['key' => 'mutate-json', 'label' => 'Wklej JSON', 'hint' => 'Z ChatGPT, bez tokenów', 'icon' => 'bi-braces'],
                    ['key' => 'mutate-category', 'label' => 'Kategorie', 'hint' => 'Uzupełnij / ujednolić', 'icon' => 'bi-tags'],
                    ['key' => 'mutate-assign', 'label' => 'Przypisz', 'hint' => 'Do osoby / sprintu', 'icon' => 'bi-person-plus'],
                    ['key' => 'mutate-refine', 'label' => 'Doprecyzuj', 'hint' => 'Opis, AC, priorytet', 'icon' => 'bi-magic'],
                    ['key' => 'mutate-export', 'label' => 'Eksport JSON', 'hint' => 'Dla promptu / reimport', 'icon' => 'bi-box-arrow-up'],
                ],
            ],
        ];
    }

    /**
     * Liście podpięte pod backend w danym widoku. Reszta UI jest „w budowie”.
     *
     * @return list<string>
     */
    public static function enabledKeys(string $context): array
    {
        return match ($context) {
            self::CONTEXT_GRID => [
                'summary-brief',
                'summary-standup',
                'summary-risks',
                'import-json',
                'import-list',
                'export-json',
                'export-csv',
                'mutate-json',
                'mutate-category',
                'mutate-refine',
                'mutate-export',
            ],
            self::CONTEXT_TASK => [
                'create-task',
            ],
            default => [],
        };
    }

    /**
     * @deprecated Użyj enabledKeys(CONTEXT_GRID)
     *
     * @return list<string>
     */
    public static function dispatchedKeys(): array
    {
        return self::enabledKeys(self::CONTEXT_GRID);
    }

    public static function shouldDispatch(string $key, string $context = self::CONTEXT_GRID): bool
    {
        return in_array($key, self::enabledKeys($context), true);
    }
}
