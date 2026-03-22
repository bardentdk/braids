<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number', 'client_id', 'coupon_id', 'status',
        'subtotal', 'discount_amount', 'shipping_amount', 'tax_rate', 'tax_amount', 'total',
        'shipping_address', 'shipping_method', 'tracking_number', 'tracking_url',
        'client_notes', 'admin_notes', 'paid_at', 'shipped_at',
        'delivered_at', 'cancelled_at', 'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'status'           => OrderStatus::class,
            'subtotal'         => 'float',
            'discount_amount'  => 'float',
            'shipping_amount'  => 'float',
            'tax_rate'         => 'float',
            'tax_amount'       => 'float',
            'total'            => 'float',
            'shipping_address' => 'array',
            'paid_at'          => 'datetime',
            'shipped_at'       => 'datetime',
            'delivered_at'     => 'datetime',
            'cancelled_at'     => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($order) {
            if (! $order->order_number) {
                $order->order_number = self::generateOrderNumber();
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        $year  = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;
        return "ORD-{$year}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function getIsPaidAttribute(): bool
    {
        return ! is_null($this->paid_at);
    }

    /* ── Relations ─────────────────────────────── */

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /* ── Scopes ────────────────────────────────── */

    public function scopeByStatus($query, OrderStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }
}