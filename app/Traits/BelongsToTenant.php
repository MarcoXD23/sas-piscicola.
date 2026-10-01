<?php

namespace App\Traits;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    /**
     * Inicializa el trait para agregar el Global Scope y asignar automáticamente tenant_id al crear.
     */
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                if (! empty($model->finca_id)) {
                    $model->tenant_id = $model->finca_id;
                } else {
                    $tenantId = TenantScope::getActiveTenantId();
                    if ($tenantId !== null) {
                        $model->tenant_id = $tenantId;
                    }
                }
            }

            if (empty($model->finca_id) && ! empty($model->tenant_id)) {
                $model->finca_id = $model->tenant_id;
            }
        });
    }

    /**
     * Relación con el Tenant (Finca).
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
