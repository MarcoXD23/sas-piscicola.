<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    /**
     * Tenant ID activo fijado manualmente para contextos de API o pruebas.
     */
    protected static ?int $activeTenantId = null;

    /**
     * Fija explícitamente el tenant activo para la ejecución actual.
     */
    public static function setActiveTenantId(?int $tenantId): void
    {
        self::$activeTenantId = $tenantId;
    }

    /**
     * Obtiene el tenant ID activo actual (por fijación manual o por usuario autenticado).
     */
    public static function getActiveTenantId(): ?int
    {
        if (self::$activeTenantId !== null) {
            return self::$activeTenantId;
        }

        if (Auth::check()) {
            return Auth::user()->tenant_id ?? Auth::user()->finca_id ?? null;
        }

        return null;
    }

    /**
     * Aplica el filtro global estricto por tenant_id a la consulta.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = self::getActiveTenantId();

        if ($tenantId !== null) {
            $builder->where($model->qualifyColumn('tenant_id'), $tenantId);
        }
    }
}
