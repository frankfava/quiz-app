<?php

namespace App\Tenancy\Events;

use App\Tenancy\Models\Tenant;

class MadeTenantCurrentEvent
{
    public function __construct(
        public Tenant $tenant
    ) {}
}
