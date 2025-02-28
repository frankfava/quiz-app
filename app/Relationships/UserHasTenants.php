<?php

namespace App\Relationships;

use App\Models\Tenant;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Builder;

trait UserHasTenants
{
    protected static string $tenantForeignKey = 'tenant_id';

    public function tenants()
    {
        return $this->belongsToMany(Tenant::class, (new TenantUser)->getTable())
            ->using(TenantUser::class)
            ->withPivot(['role'])
            ->withTimestamps();
    }

    protected static function bootUserHasTenants()
    {
        static::addGlobalScope(
            Tenant::class,
            function (Builder $builder) {
                if ($tenant = Tenant::current()) {
                    $builder->whereHas('tenants', function ($q) use ($tenant) {
                        $q->where(self::$tenantForeignKey, $tenant->id);
                    });
                }
            }
        );
    }

    public function currentTenantMeta($key = null)
    {
        if (! ($tenant = Tenant::current())) {
            return null;
        }

        return is_null($key) ? $tenant->pivot : $tenant->pivot->{$key} ?? null;
    }

    public function scopeInTenant($query)
    {
        $query->whereHas('tenants', function ($q) {
            $q->where(self::$tenantForeignKey, Tenant::current()->id);
        });
    }

    public function addToTenant(Tenant $tenant, $role = null)
    {
        return $tenant->addUser($this, $role);
    }

    public function removeFromTenant(Tenant $tenant)
    {
        return $tenant->removeUser($this);
    }

    public function canAccessTenant(?Tenant $tenant = null)
    {
        $tenant = $tenant ?? Tenant::current();

        return $tenant ? $tenant->userCanAccess($this) : false;
    }
}
