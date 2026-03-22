<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'description', 'details', 'unit_price',
        'quantity', 'discount_percent', 'total', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'unit_price'       => 'float',
            'quantity'         => 'integer',
            'discount_percent' => 'float',
            'total'            => 'float',
            'sort_order'       => 'integer',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}