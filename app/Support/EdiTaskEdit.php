<?php

namespace App\Support;

/**
 * Pola, które Edi wolno mu zaproponować. Odpowiednik $fillable — nie uprawnienie
 * do dodawania/usuwania rekordów ani do komentarzy, statusu, podzadań.
 */
final class EdiTaskEdit
{
    /**
     * @var list<string>
     */
    public const EDITABLE = [
        'name',
        'description',
        'category',
        'priority',
        'due_date',
    ];

    /**
     * @return list<string>
     */
    public static function fieldsForIntent(string $intent): array
    {
        return match ($intent) {
            'mutate-category' => ['category'],
            'mutate-refine' => self::EDITABLE,
            'mutate-json' => self::EDITABLE,
            default => [],
        };
    }

    public static function allows(string $field, string $intent): bool
    {
        return in_array($field, self::fieldsForIntent($intent), true);
    }

    /**
     * Instrukcja do wklejenia w ChatGPT — ten sam kontrakt co import JSON Ediego.
     *
     * @param  list<string>  $editable
     */
    public static function chatInstruction(array $editable = self::EDITABLE): string
    {
        $fields = implode(', ', $editable !== [] ? $editable : self::EDITABLE);

        return implode("\n", [
            'Jesteś Edim, redaktorem backlogu ChronoLogic.',
            'Dostajesz eksport istniejących zadań. Wolno Ci TYLKO proponować nowe wartości pól: '.$fields.'.',
            'Nie dodajesz zadań, nie usuwasz zadań, nie tworzysz podzadań, nie zmieniasz komentarzy, statusu ani przypisania.',
            'Odpowiedz TYLKO JSON: {"changes":[{"id":123,"field":"category","value":"Transport"}]}.',
            'value=null albo "" oznacza wyczyszczenie pola. id musi pochodzić z listy (id albo source_id). field tylko z dozwolonych.',
            'Nie zwracaj zmian, które nic nie zmieniają. Po polsku w wartościach tekstowych.',
        ]);
    }

    public static function isEmpty(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        return false;
    }

    public static function kind(mixed $from, mixed $to): ?string
    {
        $fromEmpty = self::isEmpty($from);
        $toEmpty = self::isEmpty($to);

        if ($fromEmpty && $toEmpty) {
            return null;
        }

        if (self::normalize($from) === self::normalize($to)) {
            return null;
        }

        if ($fromEmpty && ! $toEmpty) {
            return 'add';
        }

        if (! $fromEmpty && $toEmpty) {
            return 'remove';
        }

        return 'change';
    }

    public static function normalize(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return trim((string) $value);
    }

    public static function label(string $field, mixed $value): string
    {
        if (self::isEmpty($value)) {
            return 'brak';
        }

        return match ($field) {
            'priority' => match ((int) $value) {
                1 => '↓ Najniższy',
                2 => '↓ Niski',
                3 => '→ Średni',
                4 => '↑ Wysoki',
                5 => '↑ Krytyczny',
                default => (string) $value,
            },
            'due_date' => self::formatDate((string) $value),
            default => (string) $value,
        };
    }

    private static function formatDate(string $value): string
    {
        try {
            return \Carbon\Carbon::parse($value)->format('d.m.Y');
        } catch (\Throwable) {
            return $value;
        }
    }
}
