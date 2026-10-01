<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlimentacionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pond_id' => $this->pond_id,
            'pond_name' => $this->pond?->name,
            'user_id' => $this->user_id,
            'operario' => $this->user?->name,
            'fecha' => $this->feeding_date ?? $this->fecha,
            'cantidad_kg' => (float) ($this->amount_kg ?? $this->cantidad_kg),
            'tipo_concentrado' => $this->feed_name ?? $this->tipo_concentrado,
            'observaciones' => $this->observaciones,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
