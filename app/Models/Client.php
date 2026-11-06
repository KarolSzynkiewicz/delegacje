<?php

namespace App\Models;

use App\Enums\ClientType;
use App\Enums\EuropeanCountry;
use App\Traits\HasComments;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Client extends Model
{
    use HasComments, HasFactory;

    protected $fillable = [
        'name',
        'type',
        'short_name',
        'nip',
        'tax_id',
        'address',
        'city',
        'postal_code',
        'country',
        'contact_person',
        'email',
        'phone',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'type' => ClientType::class,
        'country' => EuropeanCountry::class,
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'type' => 'client',
    ];

    public function scopeCustomers(Builder $query): Builder
    {
        return $query->where('type', ClientType::Client->value);
    }

    public function scopeOfType(Builder $query, ClientType|string $type): Builder
    {
        $value = $type instanceof ClientType ? $type->value : $type;

        return $query->where('type', $value);
    }

    public function canOwnProjects(): bool
    {
        return ($this->type ?? ClientType::Client)->canOwnProjects();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(ClientContact::class)->orderByDesc('is_primary')->orderBy('name');
    }

    public function primaryContact(): HasOne
    {
        return $this->hasOne(ClientContact::class)->where('is_primary', true)->where('is_active', true);
    }

    public function contactAttempts(): HasMany
    {
        return $this->hasMany(ClientContactAttempt::class)->latest('contacted_at');
    }

    public function lastContactAttempt(): ?ClientContactAttempt
    {
        return $this->relationLoaded('contactAttempts')
            ? $this->contactAttempts->first()
            : $this->contactAttempts()->first();
    }

    public function displayName(): string
    {
        return $this->short_name ?: $this->name;
    }

    public function syncLegacyContactFields(): void
    {
        $primary = $this->contacts()
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->first();

        $this->forceFill([
            'contact_person' => $primary?->name,
            'email' => $primary?->email,
            'phone' => $primary?->phone,
        ])->saveQuietly();
    }
}
