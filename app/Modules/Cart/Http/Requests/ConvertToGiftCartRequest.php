<?php

declare(strict_types=1);

namespace Modules\Cart\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConvertToGiftCartRequest extends FormRequest
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
            'recipient_name' => 'required|string|max:255',
            'recipient_email' => 'required|email|max:255',
            'message' => 'nullable|string|max:500',
            'target_amount' => 'required|numeric|min:0',
            'expires_at' => 'nullable|date|after:today',
            'max_contributors' => 'nullable|integer|min:1|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'recipient_email.email' => 'Please provide a valid recipient email address.',
            'target_amount.min' => 'Target amount must be greater than 0.',
            'expires_at.after' => 'Expiration date must be in the future.',
        ];
    }
}
