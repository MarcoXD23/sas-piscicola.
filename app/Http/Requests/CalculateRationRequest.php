<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CalculateRationRequest extends FormRequest
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
            'feeding_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'feed_inventory_id' => ['nullable', 'exists:feed_inventories,id'],
        ];
    }

    /**
     * Nombres de atributos personalizados para mensajes de error.
     */
    public function attributes(): array
    {
        return [
            'feeding_rate' => 'tasa de alimentación (%)',
            'feed_inventory_id' => 'lote de alimento en inventario',
        ];
    }
}
