<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgendaTurno extends Model
{
    use BelongsToFinca;
    use HasFactory;

    protected $table = 'agenda_turnos';

    public const TIPO_SEMANA = 'semana';

    public const TIPO_SABADO = 'sabado';

    public const TIPO_DOMINGO = 'domingo';

    public const ROL_ALIMENTADOR = 'alimentador';

    public const ROL_SEGURIDAD_NOCHE = 'seguridad_noche';

    public const ESTADO_ACTIVO = 'activo';

    public const ESTADO_PROGRAMADO = 'programado';

    public const ESTADO_COMPLETADO = 'completado';

    public const ESTADO_CUMPLIDO = 'cumplido';

    public const ESTADO_CANCELADO = 'cancelado';

    protected $fillable = [
        'finca_id',
        'user_id',
        'fecha',
        'tipo_dia',
        'rol_asignado',
        'estado',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function finca(): BelongsTo
    {
        return $this->belongsTo(Finca::class);
    }

    /**
     * Regla de Negocio: Encadenamiento de Turno Semanal
     * Cuando se programa a un operario para alimentar de Lunes a Viernes:
     * El sistema asigna automáticamente al mismo trabajador el rol de Celador (seguridad_noche)
     * para el Domingo inmediatamente anterior.
     *
     * @return array<int, self>
     */
    public static function programarSemanaCompleta(int $fincaId, int $userId, Carbon|string $fechaLunes, ?string $observaciones = null): array
    {
        $lunes = Carbon::parse($fechaLunes)->startOfWeek();
        $creados = [];

        // 1. Domingo inmediatamente anterior: Celador Nocturno (seguridad_noche)
        $domingoAnterior = $lunes->copy()->subDay();
        $creados[] = self::updateOrCreate(
            [
                'finca_id' => $fincaId,
                'user_id' => $userId,
                'fecha' => $domingoAnterior->toDateString(),
                'rol_asignado' => self::ROL_SEGURIDAD_NOCHE,
            ],
            [
                'tipo_dia' => self::TIPO_DOMINGO,
                'estado' => self::ESTADO_PROGRAMADO,
                'observaciones' => $observaciones ?? 'Asignación automática: guardia dominical previa a turno semanal de alimentación.',
            ]
        );

        // 2. Lunes a Viernes: Alimentador diario
        for ($i = 0; $i < 5; $i++) {
            $dia = $lunes->copy()->addDays($i);
            $creados[] = self::updateOrCreate(
                [
                    'finca_id' => $fincaId,
                    'user_id' => $userId,
                    'fecha' => $dia->toDateString(),
                    'rol_asignado' => self::ROL_ALIMENTADOR,
                ],
                [
                    'tipo_dia' => self::TIPO_SEMANA,
                    'estado' => self::ESTADO_PROGRAMADO,
                    'observaciones' => $observaciones ?? 'Turno ordinario de alimentación de lunes a viernes.',
                ]
            );
        }

        return $creados;
    }

    /**
     * Regla de Negocio: Asignación Independiente de Fines de Semana (Sábados y Domingos de día)
     */
    public static function programarFinDeSemana(int $fincaId, int $userId, Carbon|string $fecha, string $rolAsignado = self::ROL_ALIMENTADOR, ?string $observaciones = null): self
    {
        $dia = Carbon::parse($fecha);
        $tipoDia = match ($dia->dayOfWeek) {
            Carbon::SATURDAY => self::TIPO_SABADO,
            Carbon::SUNDAY => self::TIPO_DOMINGO,
            default => self::TIPO_SEMANA,
        };

        return self::updateOrCreate(
            [
                'finca_id' => $fincaId,
                'user_id' => $userId,
                'fecha' => $dia->toDateString(),
                'rol_asignado' => $rolAsignado,
            ],
            [
                'tipo_dia' => $tipoDia,
                'estado' => self::ESTADO_PROGRAMADO,
                'observaciones' => $observaciones ?? 'Turno de fin de semana asignado en Agenda Operativa.',
            ]
        );
    }
}
