<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id', 'service_id', 'product_id', 'appointment_id',
        'rating', 'title', 'comment', 'images', 'is_approved',
        'is_featured', 'admin_reply', 'admin_replied_at',
    ];

    protected function casts(): array
    {
        return [
            'rating'           => 'integer',
            'images'           => 'array',
            'is_approved'      => 'boolean',
            'is_featured'      => 'boolean',
            'admin_replied_at' => 'datetime',
        ];
    }

    public function getStarsAttribute(): array
    {
        return array_map(fn($i) => $i <= $this->rating, range(1, 5));
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->where('is_approved', true);
    }
}