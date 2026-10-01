<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCalendarEventRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'event_type' => ['required', 'in:pesca_cosecha,llegada_alevinos,llegada_alimento,visita_general'],
            'event_date' => ['required', 'date'],
            'event_time' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'in:programado,en_progreso,completado,cancelado'],

            // 1. Pesca / Cosecha
            'pond_id' => ['required_if:event_type,pesca_cosecha', 'nullable', 'exists:ponds,id'],
            'estimated_kg' => ['required_if:event_type,pesca_cosecha', 'nullable', 'numeric', 'min:0.1'],

            // 2. Llegada de Alevinos
            'fingerlings_quantity' => ['required_if:event_type,llegada_alevinos', 'nullable', 'integer', 'min:1'],
            'stage' => ['nullable', 'string', 'max:100'],

            // 3. Llegada de Alimento
            'feed_type' => ['required_if:event_type,llegada_alimento', 'nullable', 'string', 'max:150'],
            'feed_bags_count' => ['nullable', 'integer', 'min:1'],
            'feed_weight_kg' => ['nullable', 'numeric', 'min:0.1'],

            // 4. Visita o Asunto General
            'inspection_notes' => ['nullable', 'string', 'max:3000'],

            // Notas adicionales y sincronización offline
            'notes' => ['nullable', 'string', 'max:1000'],
            'client_uuid' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'título del evento',
            'event_type' => 'tipo de evento',
            'event_date' => 'fecha del evento',
            'pond_id' => 'estanque de pesca',
            'estimated_kg' => 'kilos estimados de pesca',
            'fingerlings_quantity' => 'cantidad de alevinos',
            'stage' => 'etapa de alevinaje',
            'feed_type' => 'tipo de concentrado de alimento',
            'feed_bags_count' => 'número de bultos de alimento',
            'feed_weight_kg' => 'peso total de alimento',
            'inspection_notes' => 'notas de inspección del jefe',
        ];
    }
}
