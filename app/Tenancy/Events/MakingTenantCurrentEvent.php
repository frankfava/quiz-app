<?php

namespace App\Tenancy\Events;

use App\Tenancy\Models\Tenant;

class MakingTenantCurrentEvent
{
    public function __construct(
        public Tenant $tenant
    ) {}
}
