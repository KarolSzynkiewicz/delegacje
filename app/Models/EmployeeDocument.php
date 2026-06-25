<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'employee_id',
        // 'type', // USUNIĘTE - niepotrzebne, używamy 'kind'
        'valid_from',
        'valid_to',
        'kind',
        'notes',
        'file_path',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_to' => 'date:nullable',
    ];

    /**
     * Get the document (dictionary entry).
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the employee.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Etykieta statusu do UI (lista dokumentów, podgląd) — spójna z logiką Livewire.
     *
     * @return string jeden z: przyszły, ważny, wygasa_wkrotce, wygasł
     */
    public function getUiValidityStatus(): string
    {
        if ($this->valid_from && $this->valid_from->isFuture()) {
            return 'przyszły';
        }

        if ($this->kind === 'bezokresowy') {
            return 'ważny';
        }

        if ($this->isExpired()) {
            return 'wygasł';
        }

        if ($this->isExpiringSoon()) {
            return 'wygasa_wkrotce';
        }

        return 'ważny';
    }

    /**
     * Check if document is expired.
     */
    public function isExpired(): bool
    {
        if ($this->kind === 'bezokresowy') {
            return false;
        }

        if (! $this->valid_to) {
            return false;
        }

        return $this->valid_to->isPast();
    }

    /**
     * Check if document is expiring soon (within 30 days).
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        if ($this->kind === 'bezokresowy') {
            return false;
        }

        if (! $this->valid_to) {
            return false;
        }

        return $this->valid_to->isFuture() && $this->valid_to->diffInDays(now()) <= $days;
    }

    /**
     * Get the file download URL (secure route, not direct file access).
     */
    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? route('employee-documents.download', $this) : null;
    }

    /**
     * Get the inline preview URL (streams the file without forcing a download).
     */
    public function getPreviewUrlAttribute(): ?string
    {
        return $this->file_path ? route('employee-documents.preview', $this) : null;
    }

    /**
     * Check if document has a file attached.
     */
    public function hasFile(): bool
    {
        return ! empty($this->file_path);
    }

    /**
     * Lowercase file extension of the attached file (empty string when no file).
     */
    public function getFileExtensionAttribute(): string
    {
        return $this->file_path
            ? strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION))
            : '';
    }

    /**
     * Whether the attached file can be previewed inline in the browser.
     */
    public function isPreviewable(): bool
    {
        return in_array($this->file_extension, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'txt'], true);
    }
}
