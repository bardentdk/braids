<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    // ── Liste des articles (Bento Grid) ───────────────────────────────
    public function index(Request $request): Response
    {
        $posts = BlogPost::published()
            ->with('category')
            ->when($request->category, fn($q) => $q->whereHas('category', fn($c) => $c->where('slug', $request->category)))
            ->when($request->tag,      fn($q) => $q->whereJsonContains('tags', $request->tag))
            ->when($request->search,   fn($q) => $q->search($request->search))
            ->orderByDesc('is_pinned')
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->paginate(9)
            ->withQueryString();

        $categories = BlogCategory::active()
            ->withCount(['publishedPosts'])
            ->orderBy('sort_order')
            ->get();

        $hero = BlogPost::published()->pinned()->latest('published_at')->first()
             ?? BlogPost::published()->featured()->latest('published_at')->first();

        return Inertia::render('Public/Blog/Index', [
            'posts'      => $posts->through(fn($p) => $this->mapPost($p)),
            'categories' => $categories->map(fn($c) => [
                'id'          => $c->id,
                'name'        => $c->name,
                'slug'        => $c->slug,
                'color'       => $c->color,
                'icon'        => $c->icon,
                'posts_count' => $c->published_posts_count,
            ]),
            'hero'    => $hero ? $this->mapPost($hero, full: true) : null,
            'filters' => $request->only(['category', 'tag', 'search']),
            'meta'    => [
                'title'       => 'Blog — Conseils beauté & tresses | Patricia Braids',
                'description' => "Découvrez nos conseils d'experts sur les tresses, les soins capillaires et les tendances afro.",
                'og_image'    => asset('images/blog-og.jpg'),
            ],
        ]);
    }

    // ── Détail article ────────────────────────────────────────────────
    public function show(string $slug): Response
    {
        $post = BlogPost::published()
            ->with(['category', 'author'])
            ->where('slug', $slug)
            ->firstOrFail();

        $post->increment('views_count');

        $related = BlogPost::published()
            ->with('category')
            ->where('id', '!=', $post->id)
            ->when($post->blog_category_id, fn($q) => $q->where('blog_category_id', $post->blog_category_id))
            ->latest('published_at')
            ->take(3)
            ->get()
            ->map(fn($p) => $this->mapPost($p));

        $prev = BlogPost::published()->where('published_at', '<', $post->published_at)->latest('published_at')->first();
        $next = BlogPost::published()->where('published_at', '>', $post->published_at)->oldest('published_at')->first();

        return Inertia::render('Public/Blog/Show', [
            'post'    => $this->mapPost($post, full: true),
            'related' => $related,
            'prev'    => $prev ? ['slug' => $prev->slug, 'title' => $prev->title] : null,
            'next'    => $next ? ['slug' => $next->slug, 'title' => $next->title] : null,
        ]);
    }

    // ── Page catégorie ────────────────────────────────────────────────
    public function category(string $slug): Response
    {
        $category = BlogCategory::active()->where('slug', $slug)->firstOrFail();

        $posts = BlogPost::published()
            ->with('category')
            ->where('blog_category_id', $category->id)
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->paginate(9)
            ->withQueryString();

        $allCategories = BlogCategory::active()
            ->withCount(['publishedPosts'])
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Public/Blog/Category', [
            'category' => [
                'id'              => $category->id,
                'name'            => $category->name,
                'slug'            => $category->slug,
                'description'     => $category->description,
                'color'           => $category->color,
                'icon'            => $category->icon,
                'cover_image_url' => $category->cover_image_url,
            ],
            'posts'      => $posts->through(fn($p) => $this->mapPost($p)),
            'categories' => $allCategories->map(fn($c) => [
                'id'          => $c->id,
                'name'        => $c->name,
                'slug'        => $c->slug,
                'color'       => $c->color,
                'posts_count' => $c->published_posts_count,
                'is_current'  => $c->id === $category->id,
            ]),
        ]);
    }

    // ── Helper ───────────────────────────────────────────────────────

    private function mapPost(BlogPost $post, bool $full = false): array
    {
        $base = [
            'id'              => $post->id,
            'title'           => $post->title,
            'slug'            => $post->slug,
            'excerpt'         => $post->excerpt_resolved,
            'cover_image_url' => $post->cover_image_url,
            'cover_image_alt' => $post->cover_image_alt ?? $post->title,
            'reading_time'    => $post->reading_time,
            'published_date'  => $post->published_date,
            'is_featured'     => $post->is_featured,
            'is_pinned'       => $post->is_pinned,
            'views_count'     => $post->views_count,
            'tags'            => $post->tags ?? [],
            'category'        => $post->category ? [
                'name'  => $post->category->name,
                'slug'  => $post->category->slug,
                'color' => $post->category->color,
            ] : null,
        ];

        if ($full) {
            $base['content']          = $post->content;
            $base['meta_title']       = $post->meta_title_resolved;
            $base['meta_description'] = $post->meta_description_resolved;
            $base['meta_keywords']    = $post->meta_keywords;
            $base['og_image']         = $post->og_image
                ? \Illuminate\Support\Facades\Storage::url($post->og_image)
                : $post->cover_image_url;
            $base['author'] = $post->author?->name;
        }

        return $base;
    }
}