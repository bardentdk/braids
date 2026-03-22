<?php

namespace App\Http\Requests\Admin;

use App\Enums\CouponType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $couponId = $this->route('coupon')?->id;

        return [
            'code'                => ["required","string","max:50","regex:/^[A-Z0-9_\-]+$/",Rule::unique('coupons','code')->ignore($couponId)],
            'name'                => 'required|string|max:100',
            'description'         => 'nullable|string|max:500',
            'type'                => ['required', Rule::in(array_column(CouponType::cases(), 'value'))],
            'value'               => 'required|numeric|min:0',
            'min_order_amount'    => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'max_uses'            => 'nullable|integer|min:1',
            'max_uses_per_client' => 'nullable|integer|min:1',
            'is_active'           => 'boolean',
            'starts_at'           => 'nullable|date',
            'expires_at'          => 'nullable|date|after_or_equal:starts_at',
        ];
    }
}