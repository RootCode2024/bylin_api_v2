<?php

declare(strict_types=1);

namespace Modules\Promotion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePromotionRequest extends FormRequest
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
            'code' => 'sometimes|string|max:50|unique:promotions,code,' . $this->route('promotion'),
            'description' => 'nullable|string|max:1000',
            'type' => 'sometimes|in:percentage,fixed_amount,buy_x_get_y,free_shipping',
            'value' => 'sometimes|numeric|min:0',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_customer' => 'nullable|integer|min:1',
            'starts_at' => 'sometimes|date',
            'expires_at' => 'sometimes|date|after_or_equal:starts_at',
            'is_active' => 'sometimes|boolean',
            'applicable_products' => 'nullable|array',
            'applicable_categories' => 'nullable|array',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'code.unique' => 'Ce code de promotion est déjà utilisé.',
            'expires_at.after_or_equal' => 'La date d\'expiration doit être égale ou postérieure à la date de début.',
            'type.in' => 'Type de promotion invalide.',
        ];
    }
}
