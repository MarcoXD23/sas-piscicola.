<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LiquidacionSemanalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $trabajadorNombre = $this->personalTemporal?->nombre ?? $this->user?->name ?? 'Operario';

        return [
            'id' => $this->id,
            'finca_id' => $this->finca_id,
            'trabajador' => [
                'nombre' => $trabajadorNombre,
                'documento' => $this->personalTemporal?->documento ?? $this->user?->document_number,
                'tipo_vinculacion' => $this->personal_temporal_id ? 'Temporal' : 'Fijo / Planta',
            ],
            'corte_sabado' => $this->corte_sabado?->toDateString(),
            'tipo_pago' => $this->tipo_pago,
            'unidades_trabajadas' => (float) $this->unidades_trabajadas,
            'unidad_medida' => $this->tipo_pago === 'destajo' ? 'kg cosechados / faenas' : 'días trabajados (jornales)',
            'tarifa' => (float) $this->tarifa,
            'total_bruto' => (float) $this->total_bruto,
            'deducciones' => (float) $this->deducciones,
            'total_neto' => (float) $this->total_neto,
            'estado' => $this->estado,
            'observaciones' => $this->observaciones,
            'liquidado_por' => $this->liquidadoPor?->name,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
