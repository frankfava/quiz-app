<?php

namespace App\Models;

use App\Relationships\TenantHasUsers;
use App\Tenancy\Models\Tenant as BaseTenant;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tenant extends BaseTenant implements HasCurrentTenantLabel, HasName
{
    use HasFactory,
        TenantHasUsers;

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

    /* ======= URL ======= */

    public static function getUrl(self $tenant): string
    {
        return route('tenant.app', ['domain' => static::getActiveDomain($tenant)], true);
    }

    public static function getActiveDomain(self $tenant): string
    {
        if (($domain = $tenant->attributes['domain'])) {
            return $domain;
        }

        return $tenant->slug.'.'.tenancy()->mainDomain();
    }

    public function domain(): Attribute
    {
        return Attribute::make(get: fn ($value) => $value ? self::getUrl($this) : null);
    }

    public function url(): Attribute
    {
        return Attribute::make(get: fn () => static::getUrl($this));
    }

    public function activeDomain(): Attribute
    {
        return Attribute::make(get: fn () => parse_url($this->url, PHP_URL_HOST));
    }

    public function adminUrl(): Attribute
    {
        return Attribute::make(get: fn () => route('filament.admin.pages.dashboard', ['tenant' => $this->slug]));
    }

    /* ======= Role ======= */

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
