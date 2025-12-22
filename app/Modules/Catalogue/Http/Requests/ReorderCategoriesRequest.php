<?php

declare(strict_types=1);

namespace Modules\Catalogue\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderCategoriesRequest extends FormRequest
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
            'categories' => 'required|array|min:1',
            'categories.*.id' => 'required|uuid|exists:categories,id',
            'categories.*.order' => 'required|integer|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'categories.required' => 'Categories array is required.',
            'categories.min' => 'At least one category is required.',
            'categories.*.id.exists' => 'One or more category IDs are invalid.',
            'categories.*.order.min' => 'Order value must be 0 or greater.',
        ];
    }
}
