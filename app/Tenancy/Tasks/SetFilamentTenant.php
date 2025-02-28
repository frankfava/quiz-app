<?php

namespace App\Tenancy\Tasks;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;

class SetFilamentTenant implements SwitchTenantTask
{
    public function makeCurrent(Model|Tenant $tenant): void
    {
        // Update Filament Tenant if in use
        if (class_exists($filament = \Filament\Facades\Filament::class)) {
            if ($filament::isServing()) {
                $panel = $filament::getCurrentPanel();
                if ($panel->hasTenancy() && ! $tenant->is($filament::getTenant())) {
                    $filament::setTenant($tenant);
                }
            }
        }
    }

    public function forgetCurrent(null|Model|Tenant $tenant): void
    {
        //
    }
}
