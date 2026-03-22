<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ClientRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $clientId = $this->route('client')?->id;

        return [
            'first_name'     => 'required|string|max:80',
            'last_name'      => 'required|string|max:80',
            'email'          => "required|email|unique:clients,email,{$clientId}",
            'phone'          => 'nullable|string|max:20',
            'address'        => 'nullable|string|max:255',
            'city'           => 'nullable|string|max:100',
            'postal_code'    => 'nullable|string|max:10',
            'country'        => 'nullable|string|max:100',
            'birth_date'     => 'nullable|date|before:today',
            'hair_type'      => 'nullable|string|in:1a,1b,1c,2a,2b,2c,3a,3b,3c,4a,4b,4c',
            'allergies'      => 'nullable|string|max:500',
            'notes'          => 'nullable|string|max:1000',
            'loyalty_points' => 'nullable|integer|min:0',
            'source'         => 'nullable|string|max:100',
            'newsletter'     => 'boolean',
            'is_vip'         => 'boolean',
        ];
    }
}