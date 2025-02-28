<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\SuperUserPanelProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Tenancy\TenancyServiceProvider::class,
    App\Filament\Tenancy\FilamentTenancyServiceProvider::class,
];
