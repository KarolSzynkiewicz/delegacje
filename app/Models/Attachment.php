<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    protected $fillable = [
        'file_path',
        'original_name',
        'uploaded_by',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    /**
     * @param  array<int, UploadedFile|null>  $files
     */
    public static function storeManyFor(Model $attachable, array $files, ?int $userId, string $subfolder): void
    {
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }
            $path = $file->store('attachments/'.$subfolder, 'public');
            $attachable->attachments()->create([
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'uploaded_by' => $userId,
            ]);
        }
    }

    public static function copyAllTo(Model $from, Model $to, string $subfolder): void
    {
        if (! method_exists($from, 'attachments')) {
            return;
        }

        $from->loadMissing('attachments');
        foreach ($from->attachments as $attachment) {
            if (! $attachment->file_path || ! Storage::disk('public')->exists($attachment->file_path)) {
                continue;
            }

            $extension = pathinfo($attachment->file_path, PATHINFO_EXTENSION);
            $newPath = 'attachments/'.$subfolder.'/'.uniqid('', true).($extension !== '' ? '.'.$extension : '');
            Storage::disk('public')->copy($attachment->file_path, $newPath);
            $to->attachments()->create([
                'file_path' => $newPath,
                'original_name' => $attachment->original_name,
                'uploaded_by' => $attachment->uploaded_by,
            ]);
        }
    }

    public function extension(): string
    {
        $fromName = pathinfo((string) $this->original_name, PATHINFO_EXTENSION);
        $fromPath = pathinfo((string) $this->file_path, PATHINFO_EXTENSION);
        $ext = $fromName !== '' ? $fromName : $fromPath;

        return strtolower($ext);
    }

    public function isImage(): bool
    {
        return in_array($this->extension(), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }

    public function isPdf(): bool
    {
        return $this->extension() === 'pdf';
    }

    public function isText(): bool
    {
        return $this->extension() === 'txt';
    }

    public function isPreviewable(): bool
    {
        return $this->isImage() || $this->isPdf() || $this->isText();
    }

    public function previewKind(): string
    {
        if ($this->isImage()) {
            return 'image';
        }
        if ($this->isPdf()) {
            return 'pdf';
        }
        if ($this->isText()) {
            return 'text';
        }

        return 'file';
    }

    public function mimeType(): string
    {
        return match ($this->extension()) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain; charset=UTF-8',
            default => 'application/octet-stream',
        };
    }

    protected static function booted(): void
    {
        static::deleting(function (Attachment $attachment) {
            if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }
        });
    }
}
