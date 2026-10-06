<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicFileLink
{
    /**
     * Druga nazwa tego samego pliku. Usunięcie źródłowej ścieżki zostawia kopię.
     * Gdy twardy link się nie uda, powstaje zwykła kopia bajtów.
     *
     * @return array{path: ?string, mode: 'none'|'missing'|'link'|'copy'}
     */
    public function duplicate(?string $sourcePath, string $directory): array
    {
        if ($sourcePath === null || $sourcePath === '') {
            return ['path' => null, 'mode' => 'none'];
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($sourcePath)) {
            return ['path' => null, 'mode' => 'missing'];
        }

        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $newPath = trim($directory, '/').'/'.Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');
        $targetDir = dirname($disk->path($newPath));

        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $from = $disk->path($sourcePath);
        $to = $disk->path($newPath);

        if (is_file($from) && @link($from, $to)) {
            return ['path' => $newPath, 'mode' => 'link'];
        }

        $disk->copy($sourcePath, $newPath);

        return ['path' => $newPath, 'mode' => 'copy'];
    }
}
