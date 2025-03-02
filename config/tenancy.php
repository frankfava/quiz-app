<?php

use Laravel\Sanctum\Sanctum;

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant-User Restriction
    |--------------------------------------------------------------------------
    |
    | Enforces a relationship count where a tenant can have only a certain number of users
    | and a user can belong to only a certain number of tenants.
    |
    */
    'restrict_to_user_count_per_tenant' => 1,

    'restrict_to_tenant_count_per_user' => 1,

    /*
    |--------------------------------------------------------------------------
    | Main Domain
    |--------------------------------------------------------------------------
    |
    | The main domain for the application, used for tenant subdomain routing.
    |
    */
    'main_domain' => env('APP_DOMAIN', Sanctum::currentApplicationUrlWithPort()),
];
