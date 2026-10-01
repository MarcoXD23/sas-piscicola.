<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorkScheduleRequest extends FormRequest
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
            'user_id' => ['required', 'exists:users,id'],
            'schedule_date' => ['required', 'date'],
            'shift_type' => ['required', 'string', 'max:100'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'status' => ['nullable', 'in:programado,completado,cancelado'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Nombres de atributos personalizados para mensajes de error.
     */
    public function attributes(): array
    {
        return [
            'user_id' => 'trabajador asignado',
            'schedule_date' => 'fecha del turno',
            'shift_type' => 'tipo de turno',
            'start_time' => 'hora de inicio',
            'end_time' => 'hora de fin',
            'notes' => 'notas u observaciones',
        ];
    }
}
