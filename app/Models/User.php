<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuid, Notifiable, Audited;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_INVESTOR = 'investor';

    /**
     * Mass-assignable. Deliberately excludes `status` and anything role-related:
     * those are set explicitly by trusted code (admin actions / seeders) only.
     */
    protected $fillable = ['name', 'email', 'password'];

    /** Token columns are audited through explicit auth.* events, never as raw diffs. */
    protected array $auditExclude = ['registration_token', 'registration_token_expires_at', 'password_reset_token', 'password_reset_token_expires_at', 'email_verified_at'];

    protected $hidden = ['password', 'remember_token'];

    /** Mirrors the column default so a freshly created model is already active in memory. */
    protected $attributes = ['status' => 'active'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(string $roleName): bool
    {
        return $this->hasAnyRole([$roleName]);
    }

    /** @param list<string> $roles */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function assignRole(string $roleName): void
    {
        $role = Role::firstOrCreate(['name' => $roleName]);
        $this->roles()->syncWithoutDetaching([$role->id]);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function portfolio(): HasOne
    {
        return $this->hasOne(Portfolio::class);
    }

    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class);
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(Earning::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function mortgageLoans(): HasMany
    {
        return $this->hasMany(MortgageLoan::class);
    }

    public function propertyLoans(): HasMany
    {
        return $this->hasMany(PropertyLoan::class);
    }

    public function scopeInvestors($query)
    {
        return $query->whereHas('roles', fn ($q) => $q->where('name', self::ROLE_INVESTOR));
    }
}
