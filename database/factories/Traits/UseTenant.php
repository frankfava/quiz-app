<?php

namespace Database\Factories\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;

trait UseTenant
{
    const GENERATE_TENANT = -2;

    const CREATE_TENANT = -1;

    public function createTenant()
    {
        return $this->state(function (array $attributes) {
            return [
                'tenant_id' => -1,
            ];
        });
    }

    public function generateTenant()
    {
        return $this->state(function (array $attributes) {
            return [
                'tenant_id' => -2,
            ];
        });
    }

    protected function setupTenant(Model $model)
    {
        if ($model->tenant_id === -1) {
            $model->tenant_id = Tenant::factory()->create()->id;
        } elseif ($model->tenant_id === -2) {
            $model->tenant_id = (Tenant::count() ? Tenant::all()->random() : Tenant::factory()->create())->id;
        }
    }
}
