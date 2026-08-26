<?php

namespace App\Support\Llm;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

/**
 * Buduje tekstowy kontekst promptu z etykietowanych pól, list i rekordów JSON.
 *
 * Każdy fragment jest przycinany przy dodawaniu — dzięki temu prompt nie rośnie
 * razem z danymi w bazie, a koszt zapytania jest przewidywalny.
 */
class PromptContext
{
    /** @var list<string> */
    private array $lines = [];

    public static function make(): self
    {
        return new self;
    }

    /** Jedna linia „Etykieta: wartość”. Puste wartości są pomijane. */
    public function field(string $label, ?string $value, int $limit = 400): self
    {
        $value = trim((string) $value);

        if ($value === '') {
            return $this;
        }

        $this->lines[] = $label.': '.Str::limit($value, $limit, '…');

        return $this;
    }

    /**
     * Lista wartości w jednej linii, rozdzielona średnikami.
     *
     * @param  iterable<mixed>  $items
     */
    public function list(string $label, iterable $items, int $itemLimit = 120, int $maxItems = 30): self
    {
        $values = collect($items)
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->take($maxItems)
            ->map(fn (string $item) => Str::limit($item, $itemLimit, '…'))
            ->values();

        if ($values->isEmpty()) {
            return $this;
        }

        $this->lines[] = $label.': '.$values->implode('; ');

        return $this;
    }

    /**
     * Blok rekordów jako JSON — dla kontekstu tabelarycznego (np. wybrane zadania).
     *
     * @param  iterable<mixed>  $rows
     * @param  list<string>  $only  ogranicz klucze każdego rekordu
     */
    public function records(string $label, iterable $rows, int $maxRows = 25, array $only = []): self
    {
        $data = collect($rows)
            ->take($maxRows)
            ->map(function ($row) use ($only): array {
                $row = match (true) {
                    $row instanceof Arrayable => $row->toArray(),
                    is_array($row) => $row,
                    default => (array) $row,
                };

                return $only === [] ? $row : array_intersect_key($row, array_flip($only));
            })
            ->values()
            ->all();

        if ($data === []) {
            return $this;
        }

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            return $this;
        }

        $this->lines[] = $label.' (JSON): '.$json;

        return $this;
    }

    /** Surowa linia — gdy potrzebujesz zdania zamiast pary etykieta/wartość. */
    public function line(string $text): self
    {
        $text = trim($text);

        if ($text !== '') {
            $this->lines[] = $text;
        }

        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->lines === [];
    }

    public function toPrompt(): string
    {
        return implode("\n", $this->lines);
    }

    public function __toString(): string
    {
        return $this->toPrompt();
    }
}
