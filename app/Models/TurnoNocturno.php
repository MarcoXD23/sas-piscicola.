<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TurnoNocturno extends Model
{
    use BelongsToFinca;
    use HasFactory;

    protected $table = 'turnos_nocturnos';

    public const PRECIO_KG_PESCADO = 7000.00;

    protected $fillable = [
        'finca_id',
        'user_id',
        'fecha',
        'hora_entrada',
        'hora_salida',
        'pescado_kilos_llevados',
        'descuento_pescado',
        'estado',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'hora_entrada' => 'datetime',
            'hora_salida' => 'datetime',
            'pescado_kilos_llevados' => 'float',
            'descuento_pescado' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (TurnoNocturno $turno) {
            if ($turno->pescado_kilos_llevados > 0) {
                $turno->descuento_pescado = round($turno->pescado_kilos_llevados * self::PRECIO_KG_PESCADO, 2);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
