<?php

namespace App\Tenancy\Concerns;

use App\Tenancy\Tenancy;

trait UseTenancyConfig
{
    public function tenancy(): Tenancy
    {
        return app(Tenancy::class);
    }
}
