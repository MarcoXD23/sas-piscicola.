<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RecordGrossWeightRequest extends FormRequest
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
            'gross_weight_kg' => ['required', 'numeric', 'min:0.1'],
            'baskets_count' => ['nullable', 'integer', 'min:0'],
            'basket_tare_kg' => ['nullable', 'numeric', 'min:0'],
            'observations' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'gross_weight_kg' => 'kilos brutos obtenidos en báscula',
            'baskets_count' => 'canastillas pesadas',
            'basket_tare_kg' => 'peso de tara por canastilla',
        ];
    }
}
