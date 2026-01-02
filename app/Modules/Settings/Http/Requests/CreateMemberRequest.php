<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'role' => ['required', Rule::in(['admin', 'manager', 'super_admin'])],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8'],
            'send_invitation' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est requis',
            'email.required' => 'L\'email est requis',
            'email.email' => 'L\'email doit être valide',
            'email.unique' => 'Cet email est déjà utilisé',
            'username.required' => 'Le nom d\'utilisateur est requis',
            'username.unique' => 'Ce nom d\'utilisateur est déjà utilisé',
            'username.regex' => 'Le nom d\'utilisateur ne peut contenir que des lettres, chiffres, tirets et underscores',
            'role.required' => 'Le rôle est requis',
            'role.in' => 'Le rôle sélectionné est invalide',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères',
        ];
    }
}
