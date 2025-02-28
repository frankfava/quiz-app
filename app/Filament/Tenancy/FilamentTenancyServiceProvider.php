<?php

namespace App\Filament\Tenancy;

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
}
