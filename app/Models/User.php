<?php

namespace App\Models;

use App\Relationships\UserHasTenants;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, HasName, HasTenants
{
    use HasApiTokens,
        HasFactory,
        Notifiable,
        UserHasTenants;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
    ];

    protected $hidden = [
        'pivot',
        'password',
        'remember_token',
    ];

    protected $appends = [
        'name',
        'user_role',
    ];

    protected $casts = [
        'first_name' => 'string',
        'last_name' => 'string',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public static function booted()
    {
        static::creating(function (User $user) {
            // If we try to create a user without a password, generate a random one.
            if (empty($user->password)) {
                $user->password = bcrypt(Str::random(32));
            }
        });

        static::deleting(function (User $user) {
            $user->deleteUserFromTenants();
        });
    }

    /* ======= Attributes ======= */

    public function name(): Attribute
    {
        return Attribute::make(get: fn () => implode(' ', array_filter([$this->first_name, $this->last_name])));
    }

    public function userRole(): Attribute
    {
        return Attribute::make(get: fn () => $this->pivot ? ($this->pivot->role ?? null) : null);
    }

    /* ======= Filament Access ======= */

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function canBeImpersonated()
    {
        return true || (bool) $this->tenants()->count();
    }

    /* ======= Tenant Access ======= */

    public function getTenants(Panel $panel): array|Collection
    {
        return $this->tenants;
    }

    public function canAccessTenant(?Model $tenant = null): bool
    {
        return $this->hasTenantAccess($tenant);
    }

    /* ======= Scopes ======= */

    public function scopeSafeUsers($query)
    {
        $query
            ->whereNotNull('email_verified_at');
    }
}
