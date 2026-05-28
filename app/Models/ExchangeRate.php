<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Tabela kursów wymiany walut.
 *
 * Konwencja: 1 jednostka base_currency = `rate` jednostek quote_currency.
 * Np. base=EUR, quote=PLN, rate=4.30  →  1 EUR = 4.30 PLN.
 *
 * Używana TYLKO do prezentacji ("pokaż także w PLN" w raportach).
 * Wszystkie kwoty w bazie zawsze przechowujemy w walucie źródłowej.
 */
class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'rate_date',
        'base_currency',
        'quote_currency',
        'rate',
        'source',
        'notes',
    ];

    protected $casts = [
        'rate_date' => 'date',
        'rate' => 'decimal:6',
    ];

    /**
     * Znajdź najnowszy znany kurs `base → quote` na dzień `$onDate` lub wcześniej.
     * Zwraca null jeśli kurs nie jest znany.
     */
    public static function findRate(string $base, string $quote, ?Carbon $onDate = null): ?self
    {
        $base = strtoupper($base);
        $quote = strtoupper($quote);

        if ($base === $quote) {
            return null;
        }

        $onDate = ($onDate ?? Carbon::today())->copy()->startOfDay();

        return static::query()
            ->where('base_currency', $base)
            ->where('quote_currency', $quote)
            ->where('rate_date', '<=', $onDate->toDateString())
            ->orderByDesc('rate_date')
            ->first();
    }

    /**
     * Konwertuj `$amount` z `$from` do `$to` na dzień `$onDate`.
     * Zwraca null jeśli brak kursu (i waluty są różne).
     */
    public static function convert(float $amount, string $from, string $to, ?Carbon $onDate = null): ?float
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return $amount;
        }

        $rate = static::findRate($from, $to, $onDate);
        if ($rate) {
            return round($amount * (float) $rate->rate, 2);
        }

        // Próba przez kurs odwrotny: to → from
        $inverse = static::findRate($to, $from, $onDate);
        if ($inverse && (float) $inverse->rate > 0) {
            return round($amount / (float) $inverse->rate, 2);
        }

        return null;
    }

    public function scopeForPair(Builder $query, string $base, string $quote): Builder
    {
        return $query->where('base_currency', strtoupper($base))
            ->where('quote_currency', strtoupper($quote));
    }
}
