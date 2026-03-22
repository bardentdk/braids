<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AppointmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'client_id'    => 'required|exists:clients,id',
            'service_id'   => 'required|exists:services,id',
            'date'         => 'required|date|after_or_equal:today',
            'start_time'   => 'required|date_format:H:i',
            'price'        => 'nullable|numeric|min:0',
            'status'       => 'nullable|string|in:pending,confirmed,cancelled,completed,no_show',
            'client_notes' => 'nullable|string|max:1000',
            'admin_notes'  => 'nullable|string|max:1000',
            'hair_details' => 'nullable|array',
            'deposit_paid' => 'boolean',
            'deposit_payment_method' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'date.after_or_equal' => 'La date ne peut pas être dans le passé.',
            'client_id.exists'    => 'Ce client n\'existe pas.',
            'service_id.exists'   => 'Ce service n\'existe pas.',
        ];
    }
}