<?php

namespace App\Models;

use App\Enums\CouponType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'description', 'type', 'value',
        'min_order_amount', 'max_discount_amount', 'max_uses',
        'max_uses_per_client', 'uses_count', 'is_active',
        'applicable_to', 'starts_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'type'               => CouponType::class,
            'value'              => 'float',
            'min_order_amount'   => 'float',
            'max_discount_amount'=> 'float',
            'max_uses'           => 'integer',
            'max_uses_per_client'=> 'integer',
            'uses_count'         => 'integer',
            'is_active'          => 'boolean',
            'applicable_to'      => 'array',
            'starts_at'          => 'datetime',
            'expires_at'         => 'datetime',
        ];
    }

    public function getIsValidAttribute(): bool
    {
        if (! $this->is_active) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->max_uses && $this->uses_count >= $this->max_uses) return false;
        return true;
    }

    public function calculateDiscount(float $amount): float
    {
        if ($this->type === CouponType::Percentage) {
            $discount = $amount * ($this->value / 100);
            if ($this->max_discount_amount) {
                $discount = min($discount, $this->max_discount_amount);
            }
            return round($discount, 2);
        }
        return min($this->value, $amount);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeValid($query)
    {
        return $query->where('is_active', true)
                     ->where(function ($q) {
                         $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                     })
                     ->where(function ($q) {
                         $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                     })
                     ->where(function ($q) {
                         $q->whereNull('max_uses')->orWhereColumn('uses_count', '<', 'max_uses');
                     });
    }
}