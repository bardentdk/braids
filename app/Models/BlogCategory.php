<?php
// ══════════════════════════════════════════════════════════
// app/Models/BlogCategory.php
// ══════════════════════════════════════════════════════════

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BlogCategory extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'color', 'icon',
        'cover_image', 'meta_title', 'meta_description',
        'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($cat) {
            if (! $cat->slug) {
                $cat->slug = Str::slug($cat->name);
            }
        });
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        return $this->cover_image
            ? \Illuminate\Support\Facades\Storage::url($this->cover_image)
            : null;
    }

    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }

    public function publishedPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class)
                    ->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }

    public function scopeActive($query) { return $query->where('is_active', true); }
}