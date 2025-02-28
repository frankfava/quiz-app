<?php

namespace App\Tenancy\Actions;

use App\Tenancy\Concerns\UseTenancyConfig;
use App\Tenancy\Events\ForgettingCurrentTenantEvent;
use App\Tenancy\Events\ForgotCurrentTenantEvent;
use App\Tenancy\Models\Tenant;
use App\Tenancy\Tasks\SwitchTenantTask;

class ForgetCurrentTenantAction
{
    use UseTenancyConfig;

    protected $tasksCollection;

    public function __construct()
    {
        $this->tasksCollection = app($this->tenancy()::SWITCH_TENANT_TASKS_CONTAINER_KEY);
    }

    public function execute(Tenant $tenant)
    {
        event(new ForgettingCurrentTenantEvent($tenant));

        $this->performTaskToForgetCurrentTenant($tenant);
        $this->tenancy()->clearBoundCurrentTenant($tenant);

        event(new ForgotCurrentTenantEvent($tenant));

        return $this;
    }

    protected function performTaskToForgetCurrentTenant(Tenant $tenant): self
    {
        $this->tasksCollection->each(fn (SwitchTenantTask $task) => $task->forgetCurrent($tenant));

        return $this;
    }
}
