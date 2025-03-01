<?php

namespace App\Filament\Tenancy;

use App\Models\Tenant;
use App\Tenancy\Tenancy;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class FilamentTenancyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * Override Original Filament Bindings
     *
     * @use \Filament\FilamentServiceProvider
     */
    public function register(): void
    {
        // Override Filament Manager
        $this->app->scoped('filament', function (): FilamentManager {
            return new FilamentManager;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Get the main domain
        Tenancy::getMainDomainWith(fn () => env('APP_DOMAIN'));

        // Get the Tenant Model Class
        Tenancy::getTenantModelClassWith(fn () => class_exists($filament = \Filament\Facades\Filament::class) ? $filament::getTenantModel() : Tenant::class);

        // Change the host when the tenant is active
        URL::formatHostUsing(function ($root, $route) {
            if (($tenant = Tenant::current()) && $route && $route->named('tenant.*')) {
                if ($route->httpOnly()) {
                    $scheme = 'http://';
                } elseif ($route->httpsOnly()) {
                    $scheme = 'https://';
                } else {
                    $scheme = app('url')->formatScheme();
                }

                return $scheme.Tenant::getActiveDomain($tenant);
            }

            return $root;
        });
    }
}
