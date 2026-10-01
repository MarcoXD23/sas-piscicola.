<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTratamientoSanitarioRequest extends FormRequest
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
            'lote_id' => ['nullable', 'string', 'max:80'],
            'fecha_aplicacion' => ['required', 'date'],
            'producto' => ['required', 'string', 'max:150'],
            'principio_activo' => ['nullable', 'string', 'max:150'],
            'dosis' => ['required', 'string', 'max:100'],
            'tiempo_retiro_dias' => ['required', 'integer', 'min:0'],
            'responsable' => ['nullable', 'string', 'max:150'],
            'tipo_tratamiento' => ['nullable', 'string', 'max:50'],
            'observaciones' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'estanque_id.required' => 'Debe seleccionar un estanque válido.',
            'estanque_id.exists' => 'El estanque seleccionado no existe en el sistema.',
            'fecha_aplicacion.required' => 'La fecha de aplicación es obligatoria.',
            'producto.required' => 'El nombre comercial del producto o fármaco es obligatorio.',
            'dosis.required' => 'La dosis aplicada es obligatoria.',
            'tiempo_retiro_dias.required' => 'El tiempo de retiro (carencia ICA) en días es obligatorio.',
            'tiempo_retiro_dias.min' => 'El tiempo de retiro no puede ser negativo.',
        ];
    }
}
