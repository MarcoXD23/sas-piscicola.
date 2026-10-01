<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanSuscripcion extends Model
{
    use HasFactory;

    protected $table = 'planes_suscripcion';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'max_estanques',
        'max_usuarios',
        'precio_mensual',
        'soporte_offline_pwa',
        'telemetria_iot',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'max_estanques' => 'integer',
            'max_usuarios' => 'integer',
            'precio_mensual' => 'decimal:2',
            'soporte_offline_pwa' => 'boolean',
            'telemetria_iot' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function suscripciones(): HasMany
    {
        return $this->hasMany(Suscripcion::class, 'plan_id');
    }
}
