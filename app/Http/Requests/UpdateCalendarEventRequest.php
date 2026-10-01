<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCalendarEventRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'event_type' => ['sometimes', 'required', 'in:pesca_cosecha,llegada_alevinos,llegada_alimento,visita_general'],
            'event_date' => ['sometimes', 'required', 'date'],
            'event_time' => ['nullable', 'string', 'max:20'],
            'status' => ['sometimes', 'required', 'in:programado,en_progreso,completado,cancelado'],

            // 1. Pesca / Cosecha
            'pond_id' => ['nullable', 'exists:ponds,id'],
            'estimated_kg' => ['nullable', 'numeric', 'min:0.1'],

            // 2. Llegada de Alevinos
            'fingerlings_quantity' => ['nullable', 'integer', 'min:1'],
            'stage' => ['nullable', 'string', 'max:100'],

            // 3. Llegada de Alimento
            'feed_type' => ['nullable', 'string', 'max:150'],
            'feed_bags_count' => ['nullable', 'integer', 'min:1'],
            'feed_weight_kg' => ['nullable', 'numeric', 'min:0.1'],

            // 4. Visita o Asunto General
            'inspection_notes' => ['nullable', 'string', 'max:3000'],

            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
