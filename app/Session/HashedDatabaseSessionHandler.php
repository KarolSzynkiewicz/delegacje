<?php

namespace App\Session;

use Illuminate\Session\DatabaseSessionHandler;

/**
 * Ciasteczko zostaje surowym numerkiem sesji. W tabeli sessions kolumna id
 * trzyma SHA-256 tego numerka, więc zrzut bazy nie wystarcza, żeby się pod kogoś podszyć.
 */
class HashedDatabaseSessionHandler extends DatabaseSessionHandler
{
    /**
     * write() woła read() tym samym identyfikatorem. Flaga pilnuje, żeby skrót nie poszedł drugi raz.
     */
    private bool $idAlreadyHashed = false;

    public function read($sessionId): string|false
    {
        return parent::read($this->storedId($sessionId));
    }

    public function write($sessionId, $data): bool
    {
        $storedId = $this->storedId($sessionId);
        $this->idAlreadyHashed = true;

        try {
            return parent::write($storedId, $data);
        } finally {
            $this->idAlreadyHashed = false;
        }
    }

    public function destroy($sessionId): bool
    {
        return parent::destroy($this->storedId($sessionId));
    }

    private function storedId(string $sessionId): string
    {
        if ($this->idAlreadyHashed) {
            return $sessionId;
        }

        return hash('sha256', $sessionId);
    }
}
