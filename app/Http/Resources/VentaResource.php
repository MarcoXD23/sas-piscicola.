<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VentaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'finca_id' => $this->finca_id,
            'lote_id' => $this->lote_id,
            'estanque_id' => $this->estanque_id,
            'estanque_nombre' => $this->estanque?->name,
            'harvest_order_id' => $this->harvest_order_id,
            'cliente' => $this->cliente,
            'kg_vendidos' => (float) $this->kg_vendidos,
            'precio_por_kg' => (float) $this->precio_por_kg,
            'total_venta' => (float) $this->total_venta,
            'forma_pago' => $this->forma_pago,
            'fecha' => $this->fecha?->toDateString(),
            'caja_id' => $this->caja_id,
            'responsable' => $this->user?->name,
            'notas' => $this->notas,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
