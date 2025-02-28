<?php

namespace App\Tenancy\Events;

use App\Tenancy\Models\Tenant;

class ForgotCurrentTenantEvent
{
    public function __construct(
        public Tenant $tenant
    ) {}
}
