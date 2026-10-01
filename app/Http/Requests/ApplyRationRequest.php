<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ApplyRationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'feed_inventory_id' => ['required', 'exists:feed_inventories,id'],
            'feeding_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'amount_kg' => ['nullable', 'numeric', 'min:0.01'],
            'feeding_date' => ['nullable', 'date'],
            'observations' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Nombres de atributos personalizados para mensajes de error.
     */
    public function attributes(): array
    {
        return [
            'feed_inventory_id' => 'lote de alimento en inventario',
            'feeding_rate' => 'tasa de alimentación (%)',
            'amount_kg' => 'cantidad de alimento (kg)',
            'feeding_date' => 'fecha de alimentación',
            'observations' => 'observaciones',
        ];
    }
}
