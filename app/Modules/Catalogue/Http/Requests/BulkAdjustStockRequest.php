<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkAdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'adjustments' => ['required', 'array', 'min:1', 'max:100'],
            'adjustments.*.product_id' => ['required_without:adjustments.*.variation_id', 'uuid', 'exists:products,id'],
            'adjustments.*.variation_id' => ['required_without:adjustments.*.product_id', 'uuid', 'exists:product_variations,id'],
            'adjustments.*.quantity' => ['required', 'integer', 'min:0'],
            'adjustments.*.operation' => ['required', 'in:set,add,sub'],
            'adjustments.*.reason' => ['required', 'string'],
            'adjustments.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
