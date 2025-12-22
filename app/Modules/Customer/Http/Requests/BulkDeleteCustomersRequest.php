<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteCustomersRequest extends FormRequest
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
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|uuid|exists:customers,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Veuillez sélectionner au moins un client.',
            'ids.min' => 'Veuillez sélectionner au moins un client.',
            'ids.*.exists' => 'Un ou plusieurs clients sélectionnés n\'existent pas.',
        ];
    }
}
