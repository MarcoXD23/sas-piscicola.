<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class FincaScope implements Scope
{
    /**
     * Aplica el scope a un query builder de Eloquent dado.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::hasUser()) {
            $user = Auth::user();

            // Si es Dueño / Jefe de finca sin finca_id fija, posee acceso irrestricto a todas las fincas
            if (method_exists($user, 'isOwner') && $user->isOwner() && empty($user->finca_id)) {
                if (request()->filled('finca_id')) {
                    $builder->where($model->getTable().'.finca_id', request('finca_id'));
                }

                return;
            }

            if (isset($user->finca_id)) {
                $builder->where($model->getTable().'.finca_id', $user->finca_id);
            }
        }
    }
}
