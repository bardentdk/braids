<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'first_name'  => 'required|string|max:80',
            'last_name'   => 'required|string|max:80',
            'email'       => 'required|email|max:255',
            'phone'       => 'required|string|max:20',
            'address'     => 'required|string|max:255',
            'city'        => 'required|string|max:100',
            'postal_code' => 'required|string|max:10',
            'country'     => 'nullable|string|max:100',
            'notes'       => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'address.required'     => 'L\'adresse de livraison est obligatoire.',
            'city.required'        => 'La ville est obligatoire.',
            'postal_code.required' => 'Le code postal est obligatoire.',
        ];
    }
}