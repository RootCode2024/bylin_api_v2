<?php

declare(strict_types=1);

namespace Modules\Shipping\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShippingMethodRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'carrier' => 'nullable|string|max:100',
            'base_cost' => 'required|numeric|min:0',
            'cost_per_kg' => 'nullable|numeric|min:0',
            'estimated_days_min' => 'required|integer|min:0',
            'estimated_days_max' => 'required|integer|min:0|gte:estimated_days_min',
            'is_active' => 'boolean',
            'available_countries' => 'nullable|array',
            'available_countries.*' => 'string|size:2', // ISO country codes
            'free_shipping_threshold' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'estimated_days_max.gte' => 'Maximum delivery days must be greater than or equal to minimum days.',
            'available_countries.*.size' => 'Country codes must be 2-letter ISO codes.',
        ];
    }
}
