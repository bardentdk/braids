<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $fillable = [
        'session_id', 'user_id', 'coupon_id', 'coupon_code', 'discount_amount', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'float',
            'expires_at'      => 'datetime',
        ];
    }

    public function getSubtotalAttribute(): float
    {
        return $this->items->sum(fn($item) => $item->product->price * $item->quantity);
    }

    public function getTotalAttribute(): float
    {
        return max(0, $this->subtotal - $this->discount_amount);
    }

    public function getItemsCountAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}