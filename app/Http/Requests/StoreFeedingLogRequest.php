<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFeedingLogRequest extends FormRequest
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
            'pond_id' => ['required', 'exists:ponds,id'],
            'feed_inventory_id' => ['required', 'exists:feed_inventories,id'],
            'feeding_date' => ['nullable', 'date'],
            'amount_kg' => ['required', 'numeric', 'min:0.01'],
            'observations' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Nombres de atributos personalizados para mensajes de error.
     */
    public function attributes(): array
    {
        return [
            'pond_id' => 'estanque',
            'feed_inventory_id' => 'lote de alimento en inventario',
            'feeding_date' => 'fecha de alimentación',
            'amount_kg' => 'cantidad de alimento (kg)',
            'observations' => 'observaciones',
        ];
    }
}
