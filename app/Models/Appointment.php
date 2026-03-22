<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference', 'client_id', 'service_id', 'date', 'start_time', 'end_time',
        'status', 'price', 'deposit_amount', 'deposit_paid', 'deposit_paid_at',
        'deposit_payment_method', 'client_notes', 'admin_notes', 'cancellation_reason',
        'confirmed_at', 'cancelled_at', 'completed_at', 'reminder_sent',
        'reminder_sent_at', 'hair_details',
    ];

    protected function casts(): array
    {
        return [
            'date'             => 'date',
            'status'           => AppointmentStatus::class,
            'price'            => 'float',
            'deposit_amount'   => 'float',
            'deposit_paid'     => 'boolean',
            'deposit_paid_at'  => 'datetime',
            'confirmed_at'     => 'datetime',
            'cancelled_at'     => 'datetime',
            'completed_at'     => 'datetime',
            'reminder_sent_at' => 'datetime',
            'reminder_sent'    => 'boolean',
            'hair_details'     => 'array',
        ];
    }

    /* ── Boot ──────────────────────────────────── */

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($appointment) {
            if (! $appointment->reference) {
                $appointment->reference = self::generateReference();
            }
        });
    }

    public static function generateReference(): string
    {
        $year = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;
        return "RDV-{$year}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /* ── Accessors ─────────────────────────────── */

    public function getStartTimeFormattedAttribute(): string
    {
        return substr($this->start_time, 0, 5);
    }

    public function getEndTimeFormattedAttribute(): string
    {
        return substr($this->end_time, 0, 5);
    }

    public function getDateFormattedAttribute(): string
    {
        return Carbon::parse($this->date)->locale('fr')->isoFormat('dddd D MMMM YYYY');
    }

    public function getIsUpcomingAttribute(): bool
    {
        return $this->date->isFuture() && in_array($this->status, [
            AppointmentStatus::Pending,
            AppointmentStatus::Confirmed,
        ]);
    }

    public function getAmountDueAttribute(): float
    {
        return $this->deposit_paid
            ? max(0, $this->price - $this->deposit_amount)
            : $this->price;
    }

    /* ── Relations ─────────────────────────────── */

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /* ── Scopes ────────────────────────────────── */

    public function scopeUpcoming($query)
    {
        return $query->whereDate('date', '>=', today())
                     ->whereIn('status', ['pending', 'confirmed'])
                     ->orderBy('date')
                     ->orderBy('start_time');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('date', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeByStatus($query, AppointmentStatus $status)
    {
        return $query->where('status', $status);
    }
}