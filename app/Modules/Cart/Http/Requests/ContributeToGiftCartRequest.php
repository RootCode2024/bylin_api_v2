<?php

declare(strict_types=1);

namespace Modules\Cart\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContributeToGiftCartRequest extends FormRequest
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
            'amount' => 'required|numeric|min:1|max:1000000',
            'contributor_name' => 'required|string|max:255',
            'contributor_email' => 'required|email|max:255',
            'message' => 'nullable|string|max:500',
            'is_anonymous' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Please enter a contribution amount.',
            'amount.min' => 'Contribution must be at least 1.',
            'amount.max' => 'Contribution cannot exceed 1,000,000.',
            'contributor_name.required' => 'Please enter your name.',
            'contributor_email.required' => 'Please enter your email address.',
            'contributor_email.email' => 'Please enter a valid email address.',
        ];
    }
}
