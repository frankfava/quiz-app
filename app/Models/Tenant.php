<?php

namespace App\Models;

use Filament\Models\Contracts\HasCurrentTenantLabel;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model implements HasCurrentTenantLabel, HasName
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'foc',
    ];

    protected $hidden = [
        'updated_at',
        'created_at',
    ];

    protected $appends = [
        // 'user_role',
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
