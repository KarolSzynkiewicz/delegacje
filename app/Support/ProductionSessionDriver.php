<?php

namespace App\Support;

class ProductionSessionDriver
{
    /**
     * Plikowe sesje poza lokalnym środowiskiem siedzą w dysku kontenera i znikają przy redeployu.
     * Railway ma APP_ENV=staging. Redis albo jawny database zostają bez zmian.
     */
    public static function resolve(string $environment, string $configured): string
    {
        if ($configured === 'file' && ! in_array($environment, ['local', 'testing'], true)) {
            return 'database';
        }

        return $configured;
    }
}
