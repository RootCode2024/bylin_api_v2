<?php

namespace Modules\Catalogue\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'brand_id' => 'required|exists:brands,id',
            'name' => 'required|string|max:255',
            'short_description' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|decimal:2',
            'compare_price' => 'nullable|decimal:2',
            'cost_price' => 'nullable|decimal:2',
            'status' => 'required|in:draft,published',
            'is_featured' => 'required|boolean',
            'track_inventory' => 'required|boolean',
            'stock_quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'required|integer|min:0',
            'barcode' => 'nullable|string',
            'weight' => 'nullable|decimal:2',
            'dimensions' => 'nullable|json',
            'meta_data' => 'nullable|json',
            'category_id' => 'required|exists:categories,id',
            'attribute_id' => 'required|exists:attributes,id',
            'attribute_value_id' => 'required|exists:attribute_values,id',
            'image' => 'required|string',
            'images' => 'nullable|array',
            'images.*' => 'string',
        ];
    }
}
