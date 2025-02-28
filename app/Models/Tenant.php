<?php

namespace App\Models;

use App\Tenancy\Models\Tenant as BaseTenant;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tenant extends BaseTenant implements HasCurrentTenantLabel, HasName
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'foc',
    ];

    protected $hidden = [
        'pivot',
        'updated_at',
        'created_at',
    ];

    protected $appends = [
        'user_role',
    ];

    protected $casts = [
        'name' => 'string',
        'slug' => 'string',
        'domain' => 'string',
        'foc' => 'boolean',
    ];

    /* ======= Events ======= */

    public static function booted()
    {
        static::creating(function (Tenant $tenant) {
            $tenant->slug = str((bool) $tenant->slug ? $tenant->slug : $tenant->name)->slug()->toString();
        });
    }


    /* ======= Role ======= */

    public function userRole(): Attribute
    {
        return Attribute::make(get: fn () => $this->pivot->role ?? null);
    }

    public function owner(): Attribute
    {
        return Attribute::make(get: function () {
            return $this->users()
                ->orderBy('pivot_created_at')
                ->first();
        });
    }

    /* ======= Filament Tenant Setup ======= */

    public function getFilamentName(): string
    {
        return "{$this->name}";
    }

    public function getCurrentTenantLabel(): string
    {
        return '';
    }
}
