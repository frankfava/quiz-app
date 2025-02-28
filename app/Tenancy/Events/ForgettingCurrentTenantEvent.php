<?php

namespace App\Tenancy\Events;

use App\Tenancy\Models\Tenant;

class ForgettingCurrentTenantEvent
{
    public function __construct(
        public Tenant $tenant
    ) {}
}
