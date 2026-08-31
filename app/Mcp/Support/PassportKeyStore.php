<?php

namespace App\Mcp\Support;

use App\Models\OAuthKeyPair;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Passport;

class PassportKeyStore
{
    /**
     * Przywróć pliki kluczy z bazy, jeśli zniknęły (ephemeral disk na Railway).
     *
     * Nie generuje nowych kluczy — to robi ensure() przy starcie procesu.
     */
    public static function restoreFilesIfMissing(): void
    {
        if (self::usesEnvKeys() || self::filesExist()) {
            return;
        }

        self::writeFilesFromDatabase();
    }

    /**
     * Upewnij się, że klucze Passport są stabilne między restartami kontenera.
     *
     * Kolejność: zmienne PASSPORT_* → pliki + kopia w DB → wiersz w DB → nowa para.
     */
    public static function ensure(): string
    {
        if (self::usesEnvKeys()) {
            self::persistEnvKeysToDatabase();

            return 'env';
        }

        if (self::filesExist()) {
            self::persistFilesToDatabase();

            return 'files';
        }

        if (self::writeFilesFromDatabase()) {
            return 'database';
        }

        Artisan::call('passport:keys', ['--length' => 2048, '--no-interaction' => true]);
        self::persistFilesToDatabase();

        return 'generated';
    }

    public static function usesEnvKeys(): bool
    {
        return filled(config('passport.private_key')) && filled(config('passport.public_key'));
    }

    public static function filesExist(): bool
    {
        return is_file(self::privateKeyPath()) && is_file(self::publicKeyPath());
    }

    protected static function writeFilesFromDatabase(): bool
    {
        $pair = self::storedPair();

        if ($pair === null) {
            return false;
        }

        self::writeKeyFile(self::privateKeyPath(), $pair->private_key);
        self::writeKeyFile(self::publicKeyPath(), $pair->public_key);

        return true;
    }

    protected static function persistFilesToDatabase(): void
    {
        if (! self::tableReady() || ! self::filesExist()) {
            return;
        }

        self::storePair(
            (string) file_get_contents(self::privateKeyPath()),
            (string) file_get_contents(self::publicKeyPath()),
        );
    }

    protected static function persistEnvKeysToDatabase(): void
    {
        if (! self::tableReady()) {
            return;
        }

        $private = str_replace('\\n', "\n", (string) config('passport.private_key'));
        $public = str_replace('\\n', "\n", (string) config('passport.public_key'));

        if ($private === '' || $public === '') {
            return;
        }

        self::storePair($private, $public);
    }

    protected static function storePair(string $privateKey, string $publicKey): void
    {
        OAuthKeyPair::query()->updateOrCreate(
            ['id' => 1],
            [
                'private_key' => $privateKey,
                'public_key' => $publicKey,
            ]
        );
    }

    protected static function storedPair(): ?OAuthKeyPair
    {
        if (! self::tableReady()) {
            return null;
        }

        return OAuthKeyPair::query()->first();
    }

    protected static function tableReady(): bool
    {
        try {
            return Schema::hasTable('oauth_key_pairs');
        } catch (\Throwable) {
            return false;
        }
    }

    protected static function writeKeyFile(string $path, string $contents): void
    {
        file_put_contents($path, $contents);
        chmod($path, 0600);
    }

    protected static function privateKeyPath(): string
    {
        return Passport::keyPath('oauth-private.key');
    }

    protected static function publicKeyPath(): string
    {
        return Passport::keyPath('oauth-public.key');
    }
}
