<?php

namespace App\Http\Requests\Admin;

use App\Enums\ServiceCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'                  => 'required|string|max:150',
            'category'              => ['required', Rule::in(array_column(ServiceCategory::cases(), 'value'))],
            'short_description'     => 'nullable|string|max:500',
            'description'           => 'nullable|string',
            'duration'              => 'required|integer|min:15|max:1440',
            'buffer_time'           => 'nullable|integer|min:0|max:120',
            'price'                 => 'required|numeric|min:0',
            'deposit_amount'        => 'nullable|numeric|min:0',
            'deposit_required'      => 'boolean',
            'image'                 => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'includes'              => 'nullable|array',
            'includes.*'            => 'string|max:200',
            'requirements'          => 'nullable|array',
            'requirements.*'        => 'string|max:200',
            'max_clients_per_slot'  => 'nullable|integer|min:1|max:10',
            'is_active'             => 'boolean',
            'is_featured'           => 'boolean',
            'requires_consultation' => 'boolean',
            'sort_order'            => 'nullable|integer|min:0',
        ];
    }
}