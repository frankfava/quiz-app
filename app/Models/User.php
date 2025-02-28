<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasApiTokens,
        HasFactory,
        Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'name',
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
    }

    /* ======= Attributes ======= */

    public function name(): Attribute
    {
        return Attribute::make(get: fn () => implode(' ', array_filter([$this->first_name, $this->last_name])));
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

    /* ======= Scopes ======= */

    public function scopeSafeUsers($query)
    {
        $query
            ->whereNotNull('email_verified_at');
    }
}
