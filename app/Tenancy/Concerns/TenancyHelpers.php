<?php

use App\Tenancy\Tenancy;

if (! function_exists('tenancy')) {
    function tenancy(): Tenancy
    {
        return app(Tenancy::class);
    }
}
