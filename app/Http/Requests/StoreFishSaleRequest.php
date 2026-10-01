<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFishSaleRequest extends FormRequest
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
            'customer_type' => ['required', 'in:visitante,trabajador'],
            'kilos_sold' => ['nullable', 'numeric', 'min:0.01', 'required_without:cash_received'],
            'cash_received' => ['nullable', 'numeric', 'min:1', 'required_without:kilos_sold'],
            'price_per_kg' => ['nullable', 'numeric', 'min:1'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'sale_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'in:efectivo,transferencia,descuento_nomina'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_type' => 'tipo de comprador (visitante o trabajador)',
            'kilos_sold' => 'kilos de pescado vendidos',
            'cash_received' => 'dinero en efectivo recaudado',
            'price_per_kg' => 'precio por kilo',
        ];
    }
}
