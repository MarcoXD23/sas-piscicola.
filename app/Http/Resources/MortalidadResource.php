<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MortalidadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'finca_id' => $this->finca_id,
            'estanque_id' => $this->estanque_id,
            'estanque_nombre' => $this->estanque?->name,
            'lote_id' => $this->estanque?->numero_lote ?? "LOTE-{$this->estanque_id}",
            'fecha' => $this->fecha?->toDateString(),
            'cantidad_peces' => (int) $this->cantidad_peces,
            'causa_probable' => $this->causa_probable,
            'metodo_disposicion' => $this->metodo_disposicion,
            'observaciones' => $this->observaciones,
            'registrado_por' => $this->user?->name,
            'estanque_actualizado' => $this->estanque ? [
                'poblacion_restante' => (int) $this->estanque->fish_population,
                'peso_promedio_g' => (float) $this->estanque->average_weight,
                'biomasa_estimada_kg' => (float) $this->estanque->biomass,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
