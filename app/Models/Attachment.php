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

    protected static function booted(): void
    {
        static::deleting(function (Attachment $attachment) {
            if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }
        });
    }
}
