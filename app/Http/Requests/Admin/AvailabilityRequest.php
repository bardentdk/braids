<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AvailabilityRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'day_of_week'      => 'nullable|integer|between:0,6|required_without:specific_date',
            'specific_date'    => 'nullable|date|required_without:day_of_week',
            'start_time'       => 'required|date_format:H:i',
            'end_time'         => 'required|date_format:H:i|after:start_time',
            'is_blocked'       => 'boolean',
            'block_reason'     => 'nullable|string|max:255|required_if:is_blocked,true',
            'max_appointments' => 'nullable|integer|min:1|max:10',
            'is_active'        => 'boolean',
        ];
    }
}