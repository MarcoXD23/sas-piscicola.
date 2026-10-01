<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreVentaRequest extends FormRequest
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
            'cliente' => ['required', 'string', 'max:150'],
            'kg_vendidos' => ['required', 'numeric', 'min:0.1'],
            'precio_por_kg' => ['required', 'numeric', 'min:100'],
            'forma_pago' => ['required', 'string', 'in:contado,credito,transferencia,efectivo,consignacion'],
            'fecha' => ['nullable', 'date'],
            'lote_id' => ['nullable', 'string', 'max:80'],
            'estanque_id' => ['nullable', 'exists:ponds,id'],
            'harvest_order_id' => ['nullable', 'exists:harvest_orders,id'],
            'caja_id' => ['nullable', 'integer'],
            'notas' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cliente.required' => 'El nombre del cliente o empresa compradora es obligatorio.',
            'kg_vendidos.required' => 'La cantidad de kilogramos vendidos es obligatoria.',
            'kg_vendidos.min' => 'Los kilogramos vendidos deben ser mayores a 0.1.',
            'precio_por_kg.required' => 'El precio de venta por kilogramo es obligatorio.',
            'forma_pago.required' => 'La forma de pago es obligatoria.',
        ];
    }
}
