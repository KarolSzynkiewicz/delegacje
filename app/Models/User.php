<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the delegations for the user (employee).
     */
    public function delegations(): HasMany
    {
        return $this->hasMany(Delegation::class, 'employee_id');
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('administrator');
    }

    /**
     * Check if user is a manager.
     */
    public function isManager(): bool
    {
        return $this->hasRole('kierownik');
    }

    /**
     * Check if user is an employee.
     */
    public function isEmployee(): bool
    {
        return $this->hasRole('pracownik-biurowy');
    }

    /**
     * Check if user has a specific permission (using Spatie).
     * Wrapper method for backward compatibility.
     */
    public function hasPermission(string $permissionName): bool
    {
        // Admin zawsze ma wszystkie uprawnienia
        if ($this->isAdmin()) {
            return true;
        }

        return $this->hasPermissionTo($permissionName);
    }
}
