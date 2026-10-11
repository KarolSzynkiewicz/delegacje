<?php

namespace App\Support;

class ProductionSessionDriver
{
    /**
     * Plikowe sesje na produkcji siedzą w dysku kontenera i znikają przy redeployu.
     * Redis albo jawny database zostają bez zmian.
     */
    public static function resolve(string $environment, string $configured): string
    {
        if ($environment === 'production' && $configured === 'file') {
            return 'database';
        }

        return $configured;
    }
}
