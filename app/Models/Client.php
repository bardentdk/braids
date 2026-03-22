<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'first_name', 'last_name', 'email', 'phone',
        'address', 'city', 'postal_code', 'country', 'birth_date',
        'hair_type', 'allergies', 'notes', 'loyalty_points',
        'source', 'newsletter', 'is_vip',
    ];

    protected function casts(): array
    {
        return [
            'birth_date'      => 'date',
            'is_vip'          => 'boolean',
            'newsletter'      => 'boolean',
            'loyalty_points'  => 'integer',
        ];
    }

    /* ── Accessors ─────────────────────────────── */

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getInitialsAttribute(): string
    {
        return strtoupper(substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1));
    }

    public function getAvatarUrlAttribute(): string
    {
        $name = urlencode($this->full_name);
        return "https://ui-avatars.com/api/?name={$name}&background=c4956a&color=fff&bold=true&size=128";
    }

    public function getTotalSpentAttribute(): float
    {
        $orderTotal       = $this->orders()->where('status', '!=', 'cancelled')->sum('total');
        $appointmentTotal = $this->appointments()->where('status', 'completed')->sum('price');
        return $orderTotal + $appointmentTotal;
    }

    public function getAppointmentsCountAttribute(): int
    {
        return $this->appointments()->count();
    }

    /* ── Relations ─────────────────────────────── */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /* ── Scopes ────────────────────────────────── */

    public function scopeVip($query)
    {
        return $query->where('is_vip', true);
    }

    public function scopeWithNewsletter($query)
    {
        return $query->where('newsletter', true);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }
}