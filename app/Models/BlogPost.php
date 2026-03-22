<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'blog_category_id', 'user_id', 'title', 'slug', 'excerpt', 'content',
        'cover_image', 'cover_image_alt', 'reading_time', 'status',
        'is_featured', 'is_pinned', 'published_at',
        'meta_title', 'meta_description', 'meta_keywords', 'og_image',
        'ai_generated', 'ai_model', 'views_count', 'likes_count', 'tags',
    ];

    protected function casts(): array
    {
        return [
            'is_featured'    => 'boolean',
            'is_pinned'      => 'boolean',
            'ai_generated'   => 'boolean',
            'published_at'   => 'datetime',
            'tags'           => 'array',
            'views_count'    => 'integer',
            'likes_count'    => 'integer',
            'reading_time'   => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($post) {
            if (! $post->slug) {
                $post->slug = Str::slug($post->title);
            }
            // Calcul automatique du temps de lecture
            if ($post->content && ! $post->reading_time) {
                $wordCount = str_word_count(strip_tags($post->content));
                $post->reading_time = max(1, (int) ceil($wordCount / 200));
            }
        });

        static::updating(function ($post) {
            if ($post->isDirty('content')) {
                $wordCount = str_word_count(strip_tags($post->content));
                $post->reading_time = max(1, (int) ceil($wordCount / 200));
            }
            if ($post->isDirty('title') && ! $post->isDirty('slug')) {
                $post->slug = Str::slug($post->title);
            }
        });
    }

    /* ── Accessors ─────────────────────────────── */

    public function getCoverImageUrlAttribute(): ?string
    {
        return $this->cover_image
            ? \Illuminate\Support\Facades\Storage::url($this->cover_image)
            : null;
    }

    public function getPublishedDateAttribute(): string
    {
        return $this->published_at
            ? $this->published_at->locale('fr')->isoFormat('D MMMM YYYY')
            : $this->created_at->locale('fr')->isoFormat('D MMMM YYYY');
    }

    public function getIsPublishedAttribute(): bool
    {
        return $this->status === 'published'
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    public function getMetaTitleResolvedAttribute(): string
    {
        return $this->meta_title ?: $this->title . ' — Patricia Braids';
    }

    public function getMetaDescriptionResolvedAttribute(): string
    {
        return $this->meta_description
            ?: \Illuminate\Support\Str::limit(strip_tags($this->excerpt ?: $this->content), 160);
    }

    public function getExcerptResolvedAttribute(): string
    {
        return $this->excerpt
            ?: \Illuminate\Support\Str::limit(strip_tags($this->content), 200);
    }

    /* ── Relations ─────────────────────────────── */

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /* ── Scopes ────────────────────────────────── */

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->where(function ($q) {
                        $q->whereNull('published_at')           // pas de date = publié immédiatement
                        ->orWhere('published_at', '<=', now()); // ou date passée
                    });
    }

    public function scopeFeatured($query) { return $query->where('is_featured', true); }
    public function scopePinned($query)   { return $query->where('is_pinned', true); }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('excerpt', 'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%")
              ->orWhereJsonContains('tags', $search);
        });
    }
}