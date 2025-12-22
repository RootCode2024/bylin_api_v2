<?php

declare(strict_types=1);

namespace Modules\Catalogue\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveCategoryRequest extends FormRequest
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
            'new_parent_id' => [
                'nullable',
                'uuid',
                'exists:categories,id',
                function ($attribute, $value, $fail) {
                    // Prevent moving to self
                    if ($value === $this->route('id')) {
                        $fail('A category cannot be its own parent.');
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'new_parent_id.exists' => 'The specified parent category does not exist.',
        ];
    }
}
