<?php

namespace App\Tenancy\Models;

use App\Tenancy\Concerns\UseTenancyConfig;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use UseTenancyConfig;

    public function makeThisCurrent(): static
    {
        return tenancy()->makeTenantCurrent($this);
    }

    public static function makeCurrent(self $tenant): self
    {
        return tenancy()->makeTenantCurrent($tenant);
    }

    public static function current(): ?static
    {
        return tenancy()->getCurrentTenant();
    }

    public static function checkCurrent(): bool
    {
        return tenancy()->checkForCurrentTenant();
    }

    public function isCurrent(): bool
    {
        return tenancy()->isTenantCurrent($this);
    }

    public function forgetThis(): static
    {
        return tenancy()->forgetTenant($this);
    }

    public static function forgetCurrent(): ?self
    {
        return tenancy()->forgetCurrentTenant();
    }
}
