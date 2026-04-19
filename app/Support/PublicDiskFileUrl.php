<?php

namespace App\Support;

/**
 * Jednolity URL do plików zapisanych na dysku `public` (np. transport_costs/*).
 */
final class PublicDiskFileUrl
{
    public static function url(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }
        $path = trim($path);
        if ($path === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        $path = str_replace('\\', '/', $path);
        if (str_starts_with($path, 'public/')) {
            $path = substr($path, strlen('public/'));
        }
        $path = ltrim($path, '/');
        if (! str_starts_with($path, 'storage/')) {
            $path = 'storage/'.$path;
        }
        try {
            return asset($path);
        } catch (\Throwable) {
            // PHPUnit / skrypty CLI bez pełnego kernela Laravel
            return '/'.$path;
        }
    }
}
