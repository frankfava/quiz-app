<?php

namespace App\Tenancy\Actions;

use App\Tenancy\Concerns\UseTenancyConfig;
use App\Tenancy\Events\MadeTenantCurrentEvent;
use App\Tenancy\Events\MakingTenantCurrentEvent;
use App\Tenancy\Models\Tenant;
use App\Tenancy\Tasks\SwitchTenantTask;

class MakeTenantCurrentAction
{
    use UseTenancyConfig;

    protected $tasksCollection;

    public function __construct()
    {
        $this->tasksCollection = app($this->tenancy()::SWITCH_TENANT_TASKS_CONTAINER_KEY);
    }

    public function execute(Tenant $tenant)
    {
        event(new MakingTenantCurrentEvent($tenant));

        $this->performTasksToMakeTenantCurrent($tenant);
        $this->tenancy()->bindAsCurrentTenant($tenant);

        event(new MadeTenantCurrentEvent($tenant));

        return $this;
    }

    protected function performTasksToMakeTenantCurrent(Tenant $tenant): self
    {
        $this->tasksCollection->each(fn (SwitchTenantTask $task) => $task->makeCurrent($tenant));

        return $this;
    }
}
