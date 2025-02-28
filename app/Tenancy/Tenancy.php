<?php

namespace App\Tenancy;

use App\Tenancy\Models\Tenant as BaseTenant;

class Tenancy
{
    const SWITCH_TENANT_TASKS_CONTAINER_KEY = 'FilamentTenantTasks';

    /**
     * Get main domain
     */
    public function mainDomain(): string
    {
        return env('APP_DOMAIN');
    }

    /**
     * This key will be used to bind the current tenant in the container.
     */
    public function currentTenantContainerKey(): string
    {
        return 'currentTenant';
    }

    /**
     * These fields are used by tenant:artisan command to match one or more tenant
     */
    public function getTenantSearchFields(): array
    {
        return ['id'];
    }

    /**
     * Get the Tenant Class Name. This class is the model used for storing configuration on tenants.
     */
    public function getTenantModelClass(): ?string
    {
        return class_exists($filament = \Filament\Facades\Filament::class) ? $filament::getTenantModel() : BaseTenant::class;
    }

    /**
     * Return the instantiated Model for the Tenant
     *
     * It must be or extend `App\Tenancy\Models\Tenant::class`
     */
    public function getTenantModel(): ?BaseTenant
    {
        if ($modelClass = $this->getTenantModelClass()) {
            try {
                $model = new $modelClass;
                if ($model instanceof BaseTenant) {
                    return $model;
                }
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * These tasks will be performed when switching tenants.
     *
     * A valid task is any class that implements App\Tenancy\Tasks\SwitchTenantTask
     */
    public function getTenantSwitchingTasks(): array
    {
        return [
            \App\Tenancy\Tasks\PrefixCacheTask::class,
        ];
    }

    /**
     * Bind to container as Current Tenant
     */
    public function bindAsCurrentTenant(BaseTenant $tenant): self
    {
        $this->clearBoundCurrentTenant();

        app()->instance($this->currentTenantContainerKey(), $tenant);

        return $this;
    }

    /**
     * Clear from container
     */
    public function clearBoundCurrentTenant(): self
    {
        if (app()->has($containerKey = $this->currentTenantContainerKey())) {
            app()->forgetInstance($containerKey);
        }

        return $this;
    }

    /**
     * Make a tenant the current, adding it to the container
     */
    public function makeTenantCurrent(BaseTenant $tenant): BaseTenant
    {
        if ($this->isTenantCurrent($tenant)) {
            return $tenant;
        }

        // Forget current before setting new
        if (! is_null($current = $this->getCurrentTenant())) {
            $this->forgetTenant($current);
        }

        // Execute Action - Events, Tasks and Binding
        app(Actions\MakeTenantCurrentAction::class)->execute($tenant);
        app(Tasks\SetFilamentTenant::class)->makeCurrent($tenant);

        return $tenant;
    }

    /**
     * Forget the current tenant if there is one
     */
    public function forgetCurrentTenant(): ?BaseTenant
    {
        $tenant = $this->getCurrentTenant();

        // Exit if there is no current Tenant
        if (is_null($tenant)) {
            return null;
        }

        $this->forgetTenant($tenant);

        return $tenant;
    }

    /**
     * forget a specific tenant if it is current
     */
    public function forgetTenant(BaseTenant $tenant): BaseTenant
    {
        if (! $this->isTenantCurrent($tenant)) {
            return $tenant;
        }

        // Execute Action - Events, Tasks and Binding
        app(Actions\ForgetCurrentTenantAction::class)->execute($tenant);
        app(Tasks\SetFilamentTenant::class)->forgetCurrent($tenant);

        return $tenant;
    }

    /**
     * Get the Current tenant from the container
     */
    public function getCurrentTenant(): ?BaseTenant
    {
        if (! app()->has($containerKey = $this->currentTenantContainerKey())) {
            return null;
        }

        return app($containerKey);
    }

    /**
     * Check if there is a current tenant
     */
    public function checkForCurrentTenant(): bool
    {
        return $this->getCurrentTenant() !== null;
    }

    /**
     * See if a specific tenant is the current
     */
    public function isTenantCurrent(?BaseTenant $tenant): bool
    {
        if (is_null($tenant) || is_null($current = $this->getCurrentTenant())) {
            return false;
        }

        return $current->is($tenant);
    }
}
