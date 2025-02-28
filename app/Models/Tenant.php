<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'domain',
        'foc',
    ];

    protected $casts = [
        'name' => 'string',
        'foc' => 'boolean',
        'slug' => 'string',
        'domain' => 'string',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function (Tenant $tenant) {
            $tenant->slug = str((bool) $tenant->slug ? $tenant->slug : $tenant->name)->slug()->toString();
        });
    }
}