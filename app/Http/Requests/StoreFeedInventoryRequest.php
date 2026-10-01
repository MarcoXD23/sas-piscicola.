<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFeedInventoryRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'feed_type' => ['nullable', 'string', 'max:255'],
            'protein_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'bag_weight_kg' => ['nullable', 'numeric', 'min:0'],
            'quantity_kg' => ['required', 'numeric', 'min:0'],
        ];
    }
}
