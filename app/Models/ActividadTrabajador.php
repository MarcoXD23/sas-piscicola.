<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActividadTrabajador extends Model
{
    use HasFactory;

    protected $table = 'actividades_trabajadores';

    public const ACCION_ALIMENTACION = 'alimentacion';

    public const ACCION_MORTALIDAD = 'mortalidad';

    public const ACCION_TRASLADO = 'traslado_peces';

    public const ACCION_RONDA_NOCTURNA = 'ronda_nocturna';

    public const ACCION_INGRESO_ALIMENTO = 'ingreso_alimento';

    public const ACCION_TAREA_COMPLETADA = 'tarea_completada';

    protected $fillable = [
        'user_id',
        'rol_momento',
        'tipo_accion',
        'descripcion',
        'estanque_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function estanque(): BelongsTo
    {
        return $this->belongsTo(Pond::class, 'estanque_id');
    }

    /**
     * Registra automáticamente una actividad de trazabilidad del trabajador.
     */
    public static function registrar(
        User|int $user,
        string $tipoAccion,
        string $descripcion,
        ?int $estanqueId = null,
        ?string $rolMomento = null
    ): self {
        $userModel = is_numeric($user) ? User::find($user) : $user;
        $userId = $userModel?->id ?? (int) $user;

        $rol = $rolMomento;
        if (! $rol && $userModel) {
            $badge = $userModel->badgeRolHoy();
            $rol = $badge['label'] ?? $userModel->role;
        }

        return self::create([
            'user_id' => $userId,
            'rol_momento' => $rol ?? 'Operario',
            'tipo_accion' => $tipoAccion,
            'descripcion' => $descripcion,
            'estanque_id' => $estanqueId,
        ]);
    }
}
