<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDailyLaborRequest extends FormRequest
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
            'worker_name' => ['required', 'string', 'max:255'],
            'worker_id_card' => ['nullable', 'string', 'max:50'],
            'user_id' => ['nullable', 'exists:users,id'],
            'employment_type' => ['nullable', 'in:fijo,temporal'],
            'pond_id' => ['nullable', 'exists:ponds,id'],
            'work_date' => ['nullable', 'date'],
            'labor_type' => ['required', 'in:rayadores,lavado_estanques,pesca,empaque,mantenimiento,otro'],
            'daily_wage' => ['required', 'numeric', 'min:0'], // Monto del jornal
            'hours_worked' => ['nullable', 'numeric', 'min:1', 'max:24'],
            'observations' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'worker_name' => 'nombre del trabajador de apoyo',
            'employment_type' => 'tipo de vinculación (fijo o temporal)',
            'labor_type' => 'labor realizada (rayadores, lavado de estanques, pesca, empaque)',
            'daily_wage' => 'valor del jornal',
        ];
    }
}
