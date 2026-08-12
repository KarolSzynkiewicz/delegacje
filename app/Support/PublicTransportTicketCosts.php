<?php

namespace App\Support;

final class PublicTransportTicketCosts
{
    public const DEFAULT_CURRENCY = 'PLN';

    /**
     * Pusta / brakująca waluta → PLN (select w UI pokazuje pierwszą opcję wizualnie,
     * ale Livewire często nie synchronizuje modelu, dopóki użytkownik nie kliknie selecta).
     */
    public static function normalizeCurrency(mixed $currency): string
    {
        $normalized = strtoupper(trim((string) ($currency ?? '')));

        return strlen($normalized) === 3 ? $normalized : self::DEFAULT_CURRENCY;
    }

    /**
     * @param  array<string, mixed>  $ticket
     */
    public static function isRowIncomplete(array $ticket, bool $requireAttachment = true): bool
    {
        $amount = $ticket['amount'] ?? null;
        if ($amount === null || $amount === '' || ! is_numeric($amount) || (float) $amount <= 0) {
            return true;
        }

        $currency = self::normalizeCurrency($ticket['currency'] ?? null);
        if (strlen($currency) !== 3) {
            return true;
        }

        if ($requireAttachment && empty($ticket['attachment'] ?? null) && empty($ticket['attachment_path'] ?? null)) {
            return true;
        }

        return false;
    }

    /**
     * Uzupełnia brakujące waluty w mapie kosztów biletów.
     *
     * @param  array<int|string, mixed>  $ticketCostsByEmployee
     * @return array<int|string, mixed>
     */
    public static function ensureCurrencies(array $ticketCostsByEmployee): array
    {
        foreach ($ticketCostsByEmployee as $empId => $row) {
            if (! is_array($row)) {
                continue;
            }
            $ticketCostsByEmployee[$empId]['currency'] = self::normalizeCurrency($row['currency'] ?? null);
        }

        return $ticketCostsByEmployee;
    }

    /**
     * @param  iterable<int|string>  $employeeIds
     * @param  array<int|string, array<string, mixed>>  $ticketCostsByEmployee
     */
    public static function areIncompleteForEmployees(iterable $employeeIds, array $ticketCostsByEmployee, bool $requireAttachment): bool
    {
        foreach ($employeeIds as $empId) {
            $ticket = $ticketCostsByEmployee[$empId] ?? $ticketCostsByEmployee[(string) $empId] ?? [];
            if (self::isRowIncomplete($ticket, $requireAttachment)) {
                return true;
            }
        }

        return false;
    }
}
