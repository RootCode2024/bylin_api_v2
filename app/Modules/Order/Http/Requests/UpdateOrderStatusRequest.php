<?php

declare(strict_types=1);

namespace Modules\Order\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
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
            'status' => [
                'required',
                'string',
                'in:' . implode(',', [
                    \Modules\Order\Models\Order::STATUS_PENDING,
                    \Modules\Order\Models\Order::STATUS_PROCESSING,
                    \Modules\Order\Models\Order::STATUS_CONFIRMED,
                    \Modules\Order\Models\Order::STATUS_SHIPPED,
                    \Modules\Order\Models\Order::STATUS_DELIVERED,
                    \Modules\Order\Models\Order::STATUS_CANCELLED,
                    \Modules\Order\Models\Order::STATUS_REFUNDED,
                ]),
            ],
            'note' => 'nullable|string|max:500',
            'notify_customer' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Le statut de la commande est requis.',
            'status.in' => 'Le statut sélectionné est invalide.',
            'note.max' => 'La note ne peut pas dépasser 500 caractères.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'status' => 'statut',
            'note' => 'note',
            'notify_customer' => 'notifier le client',
        ];
    }
}
