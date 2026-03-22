<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:80',
            'last_name'  => 'required|string|max:80',
            'email'      => 'required|email|max:255|unique:users,email',
            'phone'      => 'nullable|string|max:20',
            'password'   => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'newsletter' => 'boolean',
            'terms'      => 'accepted',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'    => 'Un compte existe déjà avec cet email.',
            'terms.accepted'  => 'Vous devez accepter les conditions d\'utilisation.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
        ];
    }
}