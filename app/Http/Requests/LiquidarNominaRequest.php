<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LiquidarNominaRequest extends FormRequest
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
            'personal_temporal_id' => ['nullable', 'exists:personal_temporal,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'corte_sabado' => ['required', 'date'],
            'tipo_pago' => ['required', 'string', 'in:destajo,jornal'],
            'unidades_trabajadas' => ['required', 'numeric', 'min:0'],
            'tarifa' => ['required', 'numeric', 'min:0'],
            'deducciones' => ['nullable', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string'],
        ];
    }
}
