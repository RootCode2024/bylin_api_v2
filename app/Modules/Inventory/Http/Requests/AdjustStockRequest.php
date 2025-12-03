<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustStockRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by admin middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'product_id' => 'required|uuid|exists:products,id',
            'variation_id' => 'nullable|uuid|exists:product_variations,id',
            'quantity' => 'required|integer|not_in:0', // Can be positive or negative
            'type' => 'required|in:adjustment,restock,damage,return,theft,correction',
            'reason' => 'required|string|max:500',
            'reference_number' => 'nullable|string|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'product_id.exists' => 'The specified product does not exist.',
            'variation_id.exists' => 'The specified product variation does not exist.',
            'quantity.not_in' => 'Stock adjustment quantity cannot be zero.',
            'type.in' => 'Invalid stock adjustment type.',
            'reason.required' => 'Please provide a reason for the stock adjustment.',
        ];
    }
}
