<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TratamientoSanitarioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fechaHabil = $this->fecha_habil_cosecha ?? $this->fecha_fin_retiro;
        $diasRetiro = $this->tiempo_retiro_dias ?? $this->dias_tiempo_retiro ?? 0;
        $estaEnRetiro = $fechaHabil ? Carbon::parse($fechaHabil)->greaterThanOrEqualTo(now()->toDateString()) : false;
        $diasRestantes = $fechaHabil && $estaEnRetiro ? now()->diffInDays(Carbon::parse($fechaHabil), false) + 1 : 0;

        return [
            'id' => $this->id,
            'finca_id' => $this->finca_id,
            'lote_id' => $this->lote_id ?? $this->estanque?->numero_lote,
            'estanque_id' => $this->estanque_id,
            'estanque_nombre' => $this->estanque?->name,
            'fecha_aplicacion' => $this->fecha_aplicacion?->toDateString(),
            'producto' => $this->producto,
            'principio_activo' => $this->principio_activo,
            'dosis' => $this->dosis ?? $this->dosis_aplicada,
            'tiempo_retiro_dias' => $diasRetiro,
            'fecha_habil_cosecha' => $fechaHabil ? Carbon::parse($fechaHabil)->toDateString() : null,
            'responsable' => $this->responsable ?? $this->user?->name,
            'tipo_tratamiento' => $this->tipo_tratamiento,
            'observaciones' => $this->observaciones,
            'estado_ica' => [
                'bloquea_cosecha' => $estaEnRetiro,
                'en_tiempo_retiro' => $estaEnRetiro,
                'dias_carencia_restantes' => max(0, (int) $diasRestantes),
                'mensaje_legal' => $estaEnRetiro
                    ? "BLOQUEO SANITARIO ICA ACTIVO: Cosecha y comercialización prohibidas hasta {$fechaHabil}."
                    : 'Estanque habilitado para cosecha bajo cumplimiento de retiro ICA.',
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
