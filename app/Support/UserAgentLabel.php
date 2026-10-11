<?php

namespace App\Support;

final class UserAgentLabel
{
    public static function describe(?string $userAgent): string
    {
        $browser = self::browser($userAgent);
        $platform = self::platform($userAgent);

        if ($browser === null && $platform === null) {
            return 'Nieznane urządzenie';
        }

        if ($browser === null) {
            return $platform;
        }

        if ($platform === null) {
            return $browser;
        }

        return $browser.' · '.$platform;
    }

    public static function icon(?string $userAgent): string
    {
        $platform = self::platform($userAgent);

        return match ($platform) {
            'iPhone', 'iPad', 'Android' => 'bi-phone',
            default => 'bi-laptop',
        };
    }

    public static function pushHost(?string $endpoint): string
    {
        $host = strtolower((string) parse_url((string) $endpoint, PHP_URL_HOST));

        return match (true) {
            str_contains($host, 'google') || str_contains($host, 'fcm') => 'Chrome',
            str_contains($host, 'mozilla') => 'Firefox',
            str_contains($host, 'apple') => 'Safari',
            $host !== '' => $host,
            default => 'Przeglądarka',
        };
    }

    private static function browser(?string $userAgent): ?string
    {
        $ua = (string) $userAgent;

        return match (true) {
            $ua === '' => null,
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'Chrome/') || str_contains($ua, 'CriOS/') => 'Chrome',
            str_contains($ua, 'Firefox/') || str_contains($ua, 'FxiOS/') => 'Firefox',
            str_contains($ua, 'Safari/') => 'Safari',
            default => 'Przeglądarka',
        };
    }

    private static function platform(?string $userAgent): ?string
    {
        $ua = (string) $userAgent;

        return match (true) {
            $ua === '' => null,
            str_contains($ua, 'iPhone') => 'iPhone',
            str_contains($ua, 'iPad') => 'iPad',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Mac OS') => 'macOS',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Linux') => 'Linux',
            default => null,
        };
    }
}
