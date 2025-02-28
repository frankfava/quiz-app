<?php

namespace App\Filament\Tenancy;

use App\Tenancy\Models\Tenant;
use Filament\Facades\Filament;
use Filament\FilamentManager as BaseFilamentManager;
use Illuminate\Database\Eloquent\Model;

class FilamentManager extends BaseFilamentManager
{
    /**
     * Make Tenant Current
     */
    public function setTenant(?Model $tenant, bool $isQuiet = false): void
    {
        $this->tenant = $tenant;

        $tenantModel = Filament::getTenantModel() ?? Tenant::class;

        if ($tenant && $tenant instanceof $tenantModel) {
            $this->tenant->makeThisCurrent();
        }

        // NOTE: \Filament\Events\TenantSet has been removed
    }
}
