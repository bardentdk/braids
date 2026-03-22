<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'client_id'       => 'required|exists:clients,id',
            'issue_date'      => 'required|date',
            'due_date'        => 'required|date|after_or_equal:issue_date',
            'tax_rate'        => 'nullable|numeric|between:0,100',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes'           => 'nullable|string|max:1000',
            'terms'           => 'nullable|string|max:1000',
            'items'           => 'required|array|min:1',
            'items.*.description'     => 'required|string|max:255',
            'items.*.details'         => 'nullable|string|max:500',
            'items.*.unit_price'      => 'required|numeric|min:0',
            'items.*.quantity'        => 'required|integer|min:1',
            'items.*.discount_percent'=> 'nullable|numeric|between:0,100',
        ];
    }
}