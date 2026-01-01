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
     * Check if document is expired.
     */
    public function isExpired(): bool
    {
        if ($this->kind === 'bezokresowy') {
            return false;
        }

        if (!$this->valid_to) {
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

        if (!$this->valid_to) {
            return false;
        }

        return $this->valid_to->isFuture() && $this->valid_to->diffInDays(now()) <= $days;
    }

    /**
     * Get the file URL.
     */
    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }

    /**
     * Check if document has a file attached.
     */
    public function hasFile(): bool
    {
        return !empty($this->file_path);
    }
}
