<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePersonalTemporalRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:150'],
            'documento' => ['nullable', 'string', 'max:50'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'tipo_pago' => ['required', 'string', 'in:destajo,jornal'],
            'tarifa' => ['required', 'numeric', 'min:0'],
            'deducciones' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
