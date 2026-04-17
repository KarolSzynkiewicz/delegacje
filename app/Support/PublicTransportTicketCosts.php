<?php

namespace App\Support;

final class PublicTransportTicketCosts
{
    /**
     * @param  array<string, mixed>  $ticket
     */
    public static function isRowIncomplete(array $ticket, bool $requireAttachment = true): bool
    {
        $amount = $ticket['amount'] ?? null;
        if ($amount === null || $amount === '' || ! is_numeric($amount) || (float) $amount <= 0) {
            return true;
        }

        $currency = strtoupper(trim((string) ($ticket['currency'] ?? 'PLN')));
        if (strlen($currency) !== 3) {
            return true;
        }

        if ($requireAttachment && empty($ticket['attachment'] ?? null) && empty($ticket['attachment_path'] ?? null)) {
            return true;
        }

        return false;
    }

    /**
     * @param  iterable<int|string>  $employeeIds
     * @param  array<int|string, array<string, mixed>>  $ticketCostsByEmployee
     */
    public static function areIncompleteForEmployees(iterable $employeeIds, array $ticketCostsByEmployee, bool $requireAttachment): bool
    {
        foreach ($employeeIds as $empId) {
            $ticket = $ticketCostsByEmployee[$empId] ?? [];
            if (self::isRowIncomplete($ticket, $requireAttachment)) {
                return true;
            }
        }

        return false;
    }
}
