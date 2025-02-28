<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TenantUser extends Pivot
{
    public $table = 'tenant_user';

    public $hidden = ['created_at', 'updated_at'];

    protected $casts = [
        'role' => UserRole::class,
    ];

    /* ======= Events ======= */

    public static function booted()
    {
        static::created(function (TenantUser $model) {
            event('UserWasAddedToTenant', $model->user, $model->tenant);
        });

        static::deleted(function (TenantUser $model) {
            event('UserWasRemovedFromTenant', $model->user, $model->tenant);
        });
    }

    /* ======= Helper ======= */

    public function parseData(array $data = [])
    {
        $data['role'] = $data['role'] ?? UserRole::ADMIN->value;

        return $data;
    }

    /* ======= Relationships ======= */

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
