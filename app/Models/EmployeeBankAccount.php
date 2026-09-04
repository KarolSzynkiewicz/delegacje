<?php

namespace App\Models;

use App\Traits\HasDateRange;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeBankAccount extends Model
{
    use HasDateRange, HasFactory;

    protected $fillable = [
        'employee_id',
        'account_number',
        'start_date',
        'end_date',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $this->scopeActiveAtDate($query, Carbon::today());
    }

    public function formattedAccountNumber(): string
    {
        $number = strtoupper(preg_replace('/\s+/', '', $this->account_number) ?? '');

        if (preg_match('/^\d{26}$/', $number) === 1) {
            return substr($number, 0, 2).' '.trim(chunk_split(substr($number, 2), 4, ' '));
        }

        return trim(chunk_split($number, 4, ' '));
    }

    public static function normalizeAccountNumber(string $number): string
    {
        return strtoupper(preg_replace('/\s+/', '', $number) ?? '');
    }
}
