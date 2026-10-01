<?php

namespace App\Traits;

use App\Models\Finca;
use App\Models\Scopes\FincaScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToFinca
{
    /**
     * Arranca (boot) el trait para el modelo.
     */
    protected static function bootBelongsToFinca(): void
    {
        // 1. Aplicamos el Global Scope para aislar los datos en las consultas (Selects)
        static::addGlobalScope(new FincaScope);

        // 2. Inyectamos automáticamente el finca_id al momento de crear un nuevo registro (Inserts)
        static::creating(function (Model $model) {
            if (! $model->finca_id && Auth::hasUser() && isset(Auth::user()->finca_id)) {
                $model->finca_id = Auth::user()->finca_id;
            }
        });
    }

    /**
     * Define la relación hacia el modelo Finca.
     */
    public function finca(): BelongsTo
    {
        return $this->belongsTo(Finca::class, 'finca_id');
    }
}
