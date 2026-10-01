<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RecordDispatchRequest extends FormRequest
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
            'pond_id' => ['nullable', 'integer'],
            'gross_weight_kg' => ['nullable', 'numeric'],
            'total_tare_kg' => ['nullable', 'numeric'],
            'clean_weight_kg' => ['required', 'numeric', 'min:0.1'],
            'baskets_count' => ['required', 'integer', 'min:1'],
            'batches' => ['nullable', 'array'],
            'batches.*.batch_number' => ['nullable', 'integer'],
            'batches.*.container_type' => ['nullable', 'string'],
            'batches.*.unit_tare' => ['nullable', 'numeric'],
            'batches.*.gross_kg' => ['nullable', 'numeric'],
            'batches.*.baskets' => ['nullable', 'integer'],
            'batches.*.total_tare' => ['nullable', 'numeric'],
            'batches.*.subtotal_clean' => ['nullable', 'numeric'],
            'driver_name' => ['required', 'string', 'max:255'],
            'driver_id_card' => ['nullable', 'string', 'max:50'],
            'driver_vehicle_plate' => ['required', 'string', 'max:50'],
            'destination' => ['required', 'string', 'max:255'],
            'buyer_name' => ['nullable', 'string', 'max:150'],
            'observations' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'clean_weight_kg' => 'kilos limpios despachados',
            'baskets_count' => 'número de canastas utilizadas',
            'driver_name' => 'nombre del conductor',
            'driver_id_card' => 'cédula del conductor',
            'driver_vehicle_plate' => 'placa del vehículo',
            'destination' => 'rumbo o destino del envío',
            'buyer_name' => 'comprador',
        ];
    }
}
