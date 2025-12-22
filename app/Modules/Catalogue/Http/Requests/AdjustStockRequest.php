<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required_without:variation_id', 'uuid', 'exists:products,id'],
            'variation_id' => ['required_without:product_id', 'uuid', 'exists:product_variations,id'],
            'quantity' => ['required', 'integer', 'min:0'],
            'operation' => ['required', Rule::in(['set', 'add', 'sub'])],
            'reason' => ['required', 'string', Rule::in([
                'initial_stock',
                'purchase',
                'sale',
                'return',
                'damage',
                'theft',
                'adjustment',
                'transfer',
                'other'
            ])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required_without' => 'Le produit ou la variation est requis',
            'variation_id.required_without' => 'Le produit ou la variation est requis',
            'quantity.required' => 'La quantité est requise',
            'reason.required' => 'La raison de l\'ajustement est requise',
        ];
    }
}
