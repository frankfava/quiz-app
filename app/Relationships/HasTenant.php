<?php

namespace App\Relationships;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * HasTenant
 *
 * @mixin Model
 *
 * @method BelongsTo tenant()
 */
trait HasTenant
{
    protected static string $tenantForeignKey = 'tenant_id';

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, static::$tenantForeignKey);
    }

    protected static function bootHasTenant()
    {
        static::addGlobalScope(
            Tenant::class,
            function (Builder $builder) {
                if ($tenant = Tenant::current()) {
                    $builder->where($builder->getModel()->qualifycolumn(self::$tenantForeignKey), $tenant->id);
                }
            }
        );

        static::creating(function (Model $model) {
            if (($tenant = Tenant::current()) && intval($model->tenant_id) <= 0) {
                $model->tenant_id = $tenant->id;
            }
        });
    }
}
