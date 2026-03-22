<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'reference', 'invoice_id', 'client_id', 'amount', 'method',
        'transaction_id', 'status', 'paid_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount'  => 'float',
            'method'  => PaymentMethod::class,
            'paid_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($payment) {
            if (! $payment->reference) {
                $count = self::count() + 1;
                $payment->reference = "PAY-" . date('Y') . "-" . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}