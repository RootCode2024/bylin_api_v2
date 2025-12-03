<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiatePaymentRequest extends FormRequest
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
            'order_id' => 'required|uuid|exists:orders,id',
            'payment_method' => 'required|in:fedapay,card,mobile_money,bank_transfer',
            'return_url' => 'required|url',
            'cancel_url' => 'required|url',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'order_id.exists' => 'The specified order does not exist.',
            'payment_method.in' => 'Invalid payment method selected.',
            'return_url.url' => 'Return URL must be a valid URL.',
            'cancel_url.url' => 'Cancel URL must be a valid URL.',
        ];
    }
}
