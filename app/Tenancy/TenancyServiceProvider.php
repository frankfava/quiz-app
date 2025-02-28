<?php

namespace App\Tenancy;

use Illuminate\Support\ServiceProvider;

class TenancyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Global Helpers
        require_once __DIR__.'/Concerns/TenancyHelpers.php';
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerTenancyHelper();
        $this->registerTasksCollection();
    }

    protected function registerTenancyHelper(): self
    {
        $this->app->singleton(
            Tenancy::class,
            fn (): Tenancy => new Tenancy
        );

        return $this;
    }

    protected function registerTasksCollection(): self
    {
        $this->app->singleton(
            Tenancy::SWITCH_TENANT_TASKS_CONTAINER_KEY,
            function () {
                $taskClassNames = $this->app[Tenancy::class]->getTenantSwitchingTasks();
                $tasks = collect($taskClassNames)
                    ->map(function ($taskParams, $taskClass) {
                        if (is_array($taskParams) && is_numeric($taskClass)) {
                            $taskClass = array_key_first($taskParams);
                            $taskParams = $taskParams[$taskClass];
                        }
                        if (is_numeric($taskClass)) {
                            $taskClass = $taskParams;
                            $taskParams = [];
                        }

                        return app()->makeWith($taskClass, $taskParams);
                    })
                    ->toArray();

                return collect($tasks);
            }
        );

        return $this;
    }
}
