<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMortalidadRequest extends FormRequest
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
            'estanque_id' => ['required', 'exists:ponds,id'],
            'cantidad_peces' => ['required', 'integer', 'min:1'],
            'fecha' => ['nullable', 'date'],
            'causa_probable' => ['required', 'string', 'max:100'],
            'metodo_disposicion' => ['nullable', 'string', 'in:compostaje,fosa,entierro_cal,otro'],
            'observaciones' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'estanque_id.required' => 'Debe indicar el estanque donde ocurrió la baja.',
            'estanque_id.exists' => 'El estanque seleccionado no existe.',
            'cantidad_peces.required' => 'La cantidad de peces muertos es obligatoria.',
            'cantidad_peces.min' => 'La cantidad de peces debe ser al menos 1.',
            'causa_probable.required' => 'La causa probable de mortalidad es obligatoria (ej: asfixia, depredador, hongos).',
        ];
    }
}
