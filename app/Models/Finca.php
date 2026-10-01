<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Finca extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'fincas';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'nombre',
        'codigo',
        'nit',
        'departamento',
        'municipio',
        'responsable_tecnico',
        'registro_ica',
        'ubicacion',
        'configuraciones',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'configuraciones' => 'array',
        ];
    }

    /**
     * Valores de configuración predeterminados para una nueva finca.
     */
    public const DEFAULT_CONFIG = [
        'precios' => [
            'pescado_empleado_kg' => 7000,
            'pescado_visitante_kg' => 9000,
        ],
        'operacion' => [
            'peso_tara_canastilla_kg' => 2.0,
            'dia_pesca_habitual' => 'lunes',
            'dia_pago_nomina' => 'sabado',
        ],
        'modulos_activos' => [
            'celador_nocturno' => true,
            'planta_procesamiento_merma' => true,
            'asistente_ia' => true,
            'ventas_visitantes' => true,
            'policultivo_avanzado' => true,
        ],
        'especies_habilitadas' => [
            'mojarra_roja',
            'mojarra_negra',
            'cachama',
            'bocachico',
        ],
    ];

    /**
     * Retorna true o false si el módulo especificado está habilitado para esta finca.
     */
    public function tieneModulo(string $modulo): bool
    {
        $configs = $this->configuraciones ?? self::DEFAULT_CONFIG;

        $valor = data_get($configs, "modulos_activos.{$modulo}");

        if ($valor === null) {
            $valor = data_get(self::DEFAULT_CONFIG, "modulos_activos.{$modulo}", false);
        }

        return (bool) $valor;
    }

    /**
     * Permite leer parámetros usando notación de punto (ej. precios.pescado_empleado_kg).
     */
    public function obtenerConfig(string $clave, mixed $default = null): mixed
    {
        $configs = $this->configuraciones ?? self::DEFAULT_CONFIG;

        $valor = data_get($configs, $clave);

        if ($valor !== null) {
            return $valor;
        }

        $defaultFromConst = data_get(self::DEFAULT_CONFIG, $clave);

        return $defaultFromConst ?? $default;
    }

    /**
     * Guarda el nuevo valor dentro del JSON sin sobreescribir las demás configuraciones.
     */
    public function actualizarConfig(string $clave, mixed $valor): bool
    {
        $configs = $this->configuraciones ?? self::DEFAULT_CONFIG;

        data_set($configs, $clave, $valor);

        $this->configuraciones = $configs;

        return $this->save();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'finca_id');
    }

    public function ponds(): HasMany
    {
        return $this->hasMany(Pond::class, 'finca_id');
    }

    public function suscripciones(): HasMany
    {
        return $this->hasMany(Suscripcion::class, 'finca_id');
    }

    public function suscripcionActiva(): ?Suscripcion
    {
        return $this->suscripciones()
            ->where('estado', Suscripcion::ESTADO_ACTIVA)
            ->where('fecha_vencimiento', '>=', now()->toDateString())
            ->latest('id')
            ->first();
    }
}
