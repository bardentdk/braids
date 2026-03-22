<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class BookingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'first_name'  => 'required|string|max:80',
            'last_name'   => 'required|string|max:80',
            'email'       => 'required|email|max:255',
            'phone'       => 'required|string|max:20',
            'date'        => 'required|date|after:today',
            'start_time'  => 'required|date_format:H:i',
            'notes'       => 'nullable|string|max:500',
            'hair_details'=> 'nullable|array',
        ];
    }
}