<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreHarvestOrderRequest extends FormRequest
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
            'pond_id' => ['required', 'exists:ponds,id'],
            'scheduled_date' => ['required', 'date'],
            'estimated_kg' => ['required', 'numeric', 'min:0.1'],
            'observations' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'pond_id' => 'estanque o lago a pescar',
            'scheduled_date' => 'fecha programada',
            'estimated_kg' => 'cantidad estimada en kilos',
        ];
    }
}
