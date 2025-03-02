<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Validation\ValidationException;

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
        static::creating(function (self $tenantUser) {
            $userCountPerTenant = ! is_null($c = config('tenancy.restrict_to_user_count_per_tenant')) ? (int) $c : null;

            $tenantCountPerUser = ! is_null($c = config('tenancy.restrict_to_tenant_count_per_user')) ? (int) $c : null;

            // Check if tenant already has a user
            if (! is_null($userCountPerTenant) && Tenant::find($tenantUser->tenant_id)->users()->count() >= $userCountPerTenant) {
                throw ValidationException::withMessages(['Tenant can only have '.$userCountPerTenant.' '.str('user')->plural($userCountPerTenant)->toString().' in the current configuration.']);
            }

            // Check if user already belongs to a tenant
            $connectedUser = User::withoutGlobalScope(Tenant::class)->find($tenantUser->user_id);
            if (! is_null($tenantCountPerUser) && $connectedUser->tenants()->count() >= $tenantCountPerUser) {
                throw ValidationException::withMessages(['User can only belong to '.$tenantCountPerUser.' '.str('tenant')->plural($tenantCountPerUser)->toString().' in the current configuration.']);
            }
        });

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
