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
     * Bandera estática para prevenir ciclos de recursión infinita al resolver el usuario autenticado.
     */
    protected static bool $resolving = false;

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
        return static::getTenantId();
    }

    /**
     * Obtiene el tenant ID activo actual con protección contra bucle infinito.
     */
    public static function getTenantId(): ?int
    {
        if (static::$activeTenantId !== null) {
            return static::$activeTenantId;
        }

        if (static::$resolving) {
            return null;
        }

        static::$resolving = true;

        try {
            $user = auth()->user();

            return $user?->finca_id ?? $user?->tenant_id;
        } finally {
            static::$resolving = false;
        }
    }

    /**
     * Aplica el filtro global estricto por tenant a la consulta.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Durante la resolución del usuario de sesión, evitar filtrado recursivo
        if (static::$resolving) {
            return;
        }

        // Si se está resolviendo el modelo User antes de tener una sesión autenticada en memoria,
        // no debemos intentar resolver Auth::user() para evitar ciclos infinitos con SessionGuard
        if ($model instanceof \App\Models\User && ! Auth::hasUser() && static::$activeTenantId === null) {
            return;
        }

        $tenantId = static::getTenantId();

        if ($tenantId !== null) {
            $column = in_array($model->getTable(), ['users'], true) ? 'tenant_id' : 'finca_id';
            $builder->where($model->qualifyColumn($column), $tenantId);
        }
    }
}
