<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SyncCalendarEventsRequest extends FormRequest
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
            'events' => ['required', 'array', 'min:1'],
            'events.*.client_uuid' => ['required', 'string', 'max:100'],
            'events.*.title' => ['required', 'string', 'max:255'],
            'events.*.event_type' => ['required', 'in:pesca_cosecha,llegada_alevinos,llegada_alimento,visita_general'],
            'events.*.event_date' => ['required', 'date'],
            'events.*.event_time' => ['nullable', 'string', 'max:20'],
            'events.*.status' => ['nullable', 'in:programado,en_progreso,completado,cancelado'],
            'events.*.pond_id' => ['nullable', 'exists:ponds,id'],
            'events.*.estimated_kg' => ['nullable', 'numeric'],
            'events.*.fingerlings_quantity' => ['nullable', 'integer'],
            'events.*.stage' => ['nullable', 'string', 'max:100'],
            'events.*.feed_type' => ['nullable', 'string', 'max:150'],
            'events.*.feed_bags_count' => ['nullable', 'integer'],
            'events.*.feed_weight_kg' => ['nullable', 'numeric'],
            'events.*.inspection_notes' => ['nullable', 'string'],
            'events.*.notes' => ['nullable', 'string'],
        ];
    }
}
