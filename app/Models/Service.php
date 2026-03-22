<?php

namespace App\Models;

use App\Enums\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'short_description', 'description', 'category',
        'duration', 'buffer_time', 'price', 'deposit_amount', 'deposit_required',
        'image', 'images', 'includes', 'requirements', 'max_clients_per_slot',
        'is_active', 'is_featured', 'requires_consultation', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'category'              => ServiceCategory::class,
            'duration'              => 'integer',
            'buffer_time'           => 'integer',
            'price'                 => 'float',
            'deposit_amount'        => 'float',
            'deposit_required'      => 'boolean',
            'images'                => 'array',
            'includes'              => 'array',
            'requirements'          => 'array',
            'max_clients_per_slot'  => 'integer',
            'is_active'             => 'boolean',
            'is_featured'           => 'boolean',
            'requires_consultation' => 'boolean',
        ];
    }

    /* ── Accessors ─────────────────────────────── */

    public function getImageUrlAttribute(): string
    {
        if ($this->image) return Storage::url($this->image);
        return asset('images/service-placeholder.png');
    }

    public function getDurationFormattedAttribute(): string
    {
        $h = intdiv($this->duration, 60);
        $m = $this->duration % 60;
        if ($h && $m) return "{$h}h{$m}";
        if ($h) return "{$h}h";
        return "{$m}min";
    }

    public function getTotalDurationAttribute(): int
    {
        return $this->duration + $this->buffer_time;
    }

    /* ── Relations ─────────────────────────────── */

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }

    /* ── Scopes ────────────────────────────────── */

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}