<?php

declare(strict_types=1);

namespace Modules\Shipping\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShippingMethodRequest extends FormRequest
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
            'name' => 'sometimes|string|max:255',
            'carrier' => 'sometimes|string|max:100',
            'code' => 'sometimes|string|max:50|unique:shipping_methods,code,' . $this->route('shipping_method'),
            'description' => 'nullable|string|max:1000',
            'base_cost' => 'sometimes|numeric|min:0',
            'cost_per_kg' => 'nullable|numeric|min:0',
            'cost_per_km' => 'nullable|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'min_delivery_days' => 'sometimes|integer|min:0',
            'max_delivery_days' => 'sometimes|integer|min:0|gte:min_delivery_days',
            'is_active' => 'sometimes|boolean',
            'zones' => 'nullable|array',
            'zones.*' => 'string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'code.unique' => 'This shipping method code is already in use.',
            'max_delivery_days.gte' => 'Maximum delivery days must be greater than or equal to minimum.',
        ];
    }
}
