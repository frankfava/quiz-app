<?php

namespace App\Tenancy\Tasks;

use App\Tenancy\Models\Tenant;

interface SwitchTenantTask
{
    public function makeCurrent(Tenant $tenant): void;

    public function forgetCurrent(?Tenant $tenant): void;
}
