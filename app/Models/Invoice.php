<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_number', 'client_id', 'order_id', 'appointment_id', 'status', 'type',
        'client_snapshot', 'subtotal', 'discount_amount', 'tax_rate', 'tax_amount',
        'total', 'amount_paid', 'amount_due', 'issue_date', 'due_date',
        'paid_at', 'sent_at', 'notes', 'terms', 'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'status'          => InvoiceStatus::class,
            'client_snapshot' => 'array',
            'subtotal'        => 'float',
            'discount_amount' => 'float',
            'tax_rate'        => 'float',
            'tax_amount'      => 'float',
            'total'           => 'float',
            'amount_paid'     => 'float',
            'amount_due'      => 'float',
            'issue_date'      => 'date',
            'due_date'        => 'date',
            'paid_at'         => 'datetime',
            'sent_at'         => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($invoice) {
            if (! $invoice->invoice_number) {
                $invoice->invoice_number = self::generateInvoiceNumber();
            }
        });
    }

    public static function generateInvoiceNumber(): string
    {
        $year  = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;
        return "FAC-{$year}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date->isPast() &&
               ! in_array($this->status->value, ['paid', 'cancelled']);
    }

    public function getDueDateFormattedAttribute(): string
    {
        return Carbon::parse($this->due_date)->locale('fr')->isoFormat('D MMMM YYYY');
    }

    /* ── Relations ─────────────────────────────── */

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}