<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
public function authorize(): bool
{
return true;
}

public function rules(): array
{
$userId = $this->route('id');

return [
'name' => ['sometimes', 'string', 'max:255'],
'email' => ['sometimes', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
'username' => ['sometimes', 'string', 'max:255', Rule::unique('users')->ignore($userId), 'regex:/^[a-zA-Z0-9_-]+$/'],
'role' => ['sometimes', Rule::in(['admin', 'manager', 'staff', 'viewer'])],
'phone' => ['nullable', 'string', 'max:20'],
'status' => ['sometimes', Rule::in(['active', 'inactive', 'invited', 'suspended'])],
];
}

public function messages(): array
{
return [
'email.email' => 'L\'email doit être valide',
'email.unique' => 'Cet email est déjà utilisé',
'username.unique' => 'Ce nom d\'utilisateur est déjà utilisé',
'username.regex' => 'Le nom d\'utilisateur ne peut contenir que des lettres, chiffres, tirets et underscores',
'role.in' => 'Le rôle sélectionné est invalide',
'status.in' => 'Le statut sélectionné est invalide',
];
}
}
