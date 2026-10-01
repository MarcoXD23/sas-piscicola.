<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAlimentacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pond_id' => ['required', 'exists:ponds,id'],
            'inventario_alimento_id' => ['nullable', 'exists:inventario_alimento,id'],
            'feed_inventory_id' => ['nullable'],
            'tipo_concentrado' => ['nullable', 'string', 'max:150'],
            'cantidad_kg' => ['required', 'numeric', 'min:0.1'],
            'fecha' => ['nullable', 'date'],
            'hora' => ['nullable', 'string', 'max:20'],
            'observaciones' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pond_id.required' => 'El estanque a alimentar es obligatorio.',
            'pond_id.exists' => 'El estanque seleccionado no existe.',
            'cantidad_kg.required' => 'La cantidad de alimento suministrada en kilogramos es obligatoria.',
            'cantidad_kg.min' => 'La cantidad de alimento debe ser superior a 0.1 kg.',
        ];
    }
}
