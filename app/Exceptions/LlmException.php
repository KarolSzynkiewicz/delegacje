<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Błąd komunikacji z dostawcą LLM — brak klucza, odrzucone żądanie, timeout.
 *
 * Komunikaty są po polsku, bo trafiają wprost do UI w Akcjach systemowych.
 */
class LlmException extends RuntimeException
{
    public static function notConfigured(): self
    {
        return new self('Brak skonfigurowanego dostawcy AI. Dodaj klucz API w Akcjach systemowych.');
    }

    public static function unknownProvider(string $provider): self
    {
        return new self("Nieznany dostawca AI: {$provider}.");
    }

    public static function missingKey(string $provider): self
    {
        return new self("Dostawca {$provider} nie ma zapisanego klucza API.");
    }

    public static function requestFailed(string $provider, int $status, string $body): self
    {
        $body = mb_substr(trim($body), 0, 500);

        return new self("Dostawca {$provider} zwrócił błąd HTTP {$status}: {$body}");
    }

    public static function emptyResponse(string $provider, ?string $reason = null): self
    {
        $message = "Dostawca {$provider} zwrócił odpowiedź bez treści.";

        return new self($reason ? $message.' '.$reason : $message);
    }

    public static function transportError(string $provider, string $message): self
    {
        return new self("Nie udało się połączyć z dostawcą {$provider}: {$message}");
    }
}
