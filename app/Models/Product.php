<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id', 'name', 'slug', 'short_description', 'description',
        'sku', 'price', 'compare_price', 'cost_price', 'stock',
        'low_stock_alert', 'weight', 'dimensions', 'track_stock',
        'allow_backorder', 'is_active', 'is_featured', 'is_digital',
        'thumbnail', 'tags', 'attributes', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price'           => 'float',
            'compare_price'   => 'float',
            'cost_price'      => 'float',
            'stock'           => 'integer',
            'low_stock_alert' => 'integer',
            'weight'          => 'float',
            'dimensions'      => 'array',
            'track_stock'     => 'boolean',
            'allow_backorder' => 'boolean',
            'is_active'       => 'boolean',
            'is_featured'     => 'boolean',
            'is_digital'      => 'boolean',
            'tags'            => 'array',
            'attributes'      => 'array',
        ];
    }

    /* ── Accessors ─────────────────────────────── */

    public function getThumbnailUrlAttribute(): string
    {
        if ($this->thumbnail) {
            return Storage::url($this->thumbnail);
        }
        return asset('images/product-placeholder.png');
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->compare_price && $this->compare_price > $this->price;
    }

    public function getDiscountPercentAttribute(): int
    {
        if (! $this->is_on_sale) return 0;
        return (int) round((($this->compare_price - $this->price) / $this->compare_price) * 100);
    }

    public function getIsInStockAttribute(): bool
    {
        if (! $this->track_stock) return true;
        return $this->stock > 0 || $this->allow_backorder;
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->track_stock && $this->stock <= $this->low_stock_alert && $this->stock > 0;
    }

    public function getMarginAttribute(): float
    {
        if (! $this->cost_price || $this->cost_price == 0) return 0;
        return round((($this->price - $this->cost_price) / $this->price) * 100, 2);
    }

    /* ── Relations ─────────────────────────────── */

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasMany
    {
        return $this->hasMany(ProductImage::class)->where('is_primary', true)->limit(1);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /* ── Scopes ────────────────────────────────── */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('track_stock', false)
              ->orWhere('stock', '>', 0)
              ->orWhere('allow_backorder', true);
        });
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%");
        });
    }

    public function scopeLowStock($query)
    {
        return $query->where('track_stock', true)
                     ->whereColumn('stock', '<=', 'low_stock_alert')
                     ->where('stock', '>', 0);
    }
}