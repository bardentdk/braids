<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Services\GroqService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function __construct(protected GroqService $ai) {}

    // ── Index ─────────────────────────────────────────────────────────
    public function index(Request $request): Response
    {
        $query = BlogPost::with(['category', 'author'])
            ->when($request->search,   fn($q) => $q->search($request->search))
            ->when($request->status,   fn($q) => $q->where('status', $request->status))
            ->when($request->category, fn($q) => $q->where('blog_category_id', $request->category))
            ->latest('published_at')
            ->latest();

        $posts = $query->paginate(12)->withQueryString();

        return Inertia::render('Admin/Blog/Index', [
            'posts' => $posts->through(fn($p) => [
                'id'              => $p->id,
                'title'           => $p->title,
                'slug'            => $p->slug,
                'status'          => $p->status,
                'is_featured'     => $p->is_featured,
                'is_pinned'       => $p->is_pinned,
                'ai_generated'    => $p->ai_generated,
                'reading_time'    => $p->reading_time,
                'views_count'     => $p->views_count,
                'published_at'    => $p->published_at?->locale('fr')->isoFormat('D MMM YYYY'),
                'cover_image_url' => $p->cover_image_url,
                'category'        => $p->category
                    ? ['id' => $p->category->id, 'name' => $p->category->name, 'color' => $p->category->color]
                    : null,
                'author' => ['name' => $p->author->name],
            ]),
            'filters'    => $request->only(['search', 'status', 'category']),
            'categories' => BlogCategory::active()->orderBy('name')->get(['id', 'name', 'color']),
            'stats' => [
                'total'     => BlogPost::count(),
                'published' => BlogPost::published()->count(),
                'draft'     => BlogPost::where('status', 'draft')->count(),
                'featured'  => BlogPost::featured()->count(),
                'views'     => BlogPost::sum('views_count'),
            ],
            'ai_model' => $this->ai->getModelInfo(),
        ]);
    }

    // ── Create ────────────────────────────────────────────────────────
    public function create(): Response
    {
        return Inertia::render('Admin/Blog/Create', [
            'categories' => BlogCategory::active()->orderBy('sort_order')->get(['id', 'name', 'color', 'icon']),
            'ai_model'   => $this->ai->getModelInfo(),
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────────
    public function store(Request $request): RedirectResponse
    {
        // forceFormData envoie les booléens en string et les arrays en JSON
        $request->merge($this->normalizeBooleans($request));

        $data = $request->validate([
            'blog_category_id' => 'nullable|exists:blog_categories,id',
            'title'            => 'required|string|max:255',
            'slug'             => 'nullable|string|max:255|unique:blog_posts,slug',
            'excerpt'          => 'nullable|string|max:500',
            'content'          => 'required|string',
            'cover_image'      => 'nullable|image|max:3072',
            'cover_image_alt'  => 'nullable|string|max:255',
            'status'           => 'required|in:draft,published,archived',
            'is_featured'      => 'boolean',
            'is_pinned'        => 'boolean',
            'published_at'     => 'nullable|date',
            'meta_title'       => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords'    => 'nullable|string|max:255',
            'tags'             => 'nullable|array',
            'ai_generated'     => 'boolean',
            'ai_model'         => 'nullable|string|max:100',
        ]);

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('blog', 'public');
        }

        // empty() gère : clé absente, null, ""
        if (empty($data['slug'])) {
            $data['slug'] = $this->uniqueSlug(Str::slug($data['title']));
        }

        if (($data['status'] ?? '') === 'published') {
            $data['published_at'] = isset($data['published_at']) && $data['published_at']
                ? \Carbon\Carbon::parse($data['published_at'])->utc()
                : now();
        }

        $data['user_id'] = auth()->id();

        $post = BlogPost::create($data);

        return redirect()->route('admin.blog.show', $post)
                         ->with('success', "Article \"{$post->title}\" créé.");
    }

    // ── Show ──────────────────────────────────────────────────────────
    public function show(BlogPost $blog): Response
    {
        $blog->load(['category', 'author']);

        return Inertia::render('Admin/Blog/Show', [
            'post' => [
                'id'               => $blog->id,
                'title'            => $blog->title,
                'slug'             => $blog->slug,
                'excerpt'          => $blog->excerpt,
                'content'          => $blog->content,
                'status'           => $blog->status,
                'is_featured'      => $blog->is_featured,
                'is_pinned'        => $blog->is_pinned,
                'ai_generated'     => $blog->ai_generated,
                'ai_model'         => $blog->ai_model,
                'reading_time'     => $blog->reading_time,
                'views_count'      => $blog->views_count,
                'likes_count'      => $blog->likes_count,
                'cover_image_url'  => $blog->cover_image_url,
                'cover_image_alt'  => $blog->cover_image_alt,
                'published_at'     => $blog->published_at?->locale('fr')->isoFormat('D MMMM YYYY'),
                'published_date'   => $blog->published_date,
                'meta_title'       => $blog->meta_title,
                'meta_description' => $blog->meta_description,
                'meta_keywords'    => $blog->meta_keywords,
                'tags'             => $blog->tags ?? [],
                'category'         => $blog->category
                    ? ['id' => $blog->category->id, 'name' => $blog->category->name, 'color' => $blog->category->color]
                    : null,
                'author' => ['name' => $blog->author->name],
            ],
        ]);
    }

    // ── Edit ──────────────────────────────────────────────────────────
    public function edit(BlogPost $blog): Response
    {
        return Inertia::render('Admin/Blog/Edit', [
            'post'       => $blog->load('category'),
            'categories' => BlogCategory::active()->orderBy('sort_order')->get(['id', 'name', 'color', 'icon']),
            'ai_model'   => $this->ai->getModelInfo(),
        ]);
    }

    // ── Update ────────────────────────────────────────────────────────
    public function update(Request $request, BlogPost $blog): RedirectResponse
    {
        $request->merge($this->normalizeBooleans($request));

        $data = $request->validate([
            'blog_category_id' => 'nullable|exists:blog_categories,id',
            'title'            => 'required|string|max:255',
            'slug'             => "nullable|string|max:255|unique:blog_posts,slug,{$blog->id}",
            'excerpt'          => 'nullable|string|max:500',
            'content'          => 'required|string',
            'cover_image'      => 'nullable|image|max:3072',
            'cover_image_alt'  => 'nullable|string|max:255',
            'status'           => 'required|in:draft,published,archived',
            'is_featured'      => 'boolean',
            'is_pinned'        => 'boolean',
            'published_at'     => 'nullable|date',
            'meta_title'       => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords'    => 'nullable|string|max:255',
            'tags'             => 'nullable|array',
        ]);

        if ($request->hasFile('cover_image')) {
            if ($blog->cover_image) Storage::disk('public')->delete($blog->cover_image);
            $data['cover_image'] = $request->file('cover_image')->store('blog', 'public');
        }

        if (empty($data['slug'])) {
            $data['slug'] = $blog->slug;
        }

        if (($data['status'] ?? '') === 'published') {
            $data['published_at'] = isset($data['published_at']) && $data['published_at']
                ? \Carbon\Carbon::parse($data['published_at'])->utc()
                : now();
        }

        $blog->update($data);

        return back()->with('success', 'Article mis à jour.');
    }

    // ── Destroy ───────────────────────────────────────────────────────
    public function destroy(BlogPost $blog): RedirectResponse
    {
        if ($blog->cover_image) Storage::disk('public')->delete($blog->cover_image);

        $title = $blog->title;
        $blog->delete();

        return redirect()->route('admin.blog.index')
                         ->with('success', "Article \"{$title}\" supprimé.");
    }

    // ── Toggle statut ─────────────────────────────────────────────────
    public function toggleStatus(BlogPost $blog): RedirectResponse
    {
        $newStatus = $blog->status === 'published' ? 'draft' : 'published';
        $updates   = ['status' => $newStatus];

        if ($newStatus === 'published' && ! $blog->published_at) {
            $updates['published_at'] = now();
        }

        $blog->update($updates);

        return back()->with('success', $newStatus === 'published' ? 'Article publié.' : 'Article repassé en brouillon.');
    }

    public function toggleFeatured(BlogPost $blog): RedirectResponse
    {
        $blog->update(['is_featured' => ! $blog->is_featured]);
        return back()->with('success', $blog->is_featured ? 'Article mis en avant.' : 'Article retiré de la mise en avant.');
    }

    // ══════════════════════════════════════════════════════════════════
    // ── ENDPOINTS IA ──────────────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════

    public function aiGenerate(Request $request): JsonResponse
    {
        $request->validate([
            'subject'    => 'required|string|max:255',
            'tone'       => 'nullable|in:expert,friendly,inspirational,educational',
            'keywords'   => 'nullable|string|max:255',
            'word_count' => 'nullable|integer|min:200|max:1500',
        ]);

        try {
            $data = $this->ai->generateFullPost(
                subject:   $request->subject,
                tone:      $request->tone      ?? 'expert',
                keywords:  $request->keywords  ?? null,
                wordCount: $request->word_count ?? 600,
            );

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function aiImprove(Request $request): JsonResponse
    {
        $request->validate([
            'content'     => 'required|string',
            'instruction' => 'required|string|max:500',
        ]);

        try {
            $improved = $this->ai->improveContent($request->content, $request->instruction);
            return response()->json(['success' => true, 'content' => $improved]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function aiSuggestTopics(Request $request): JsonResponse
    {
        try {
            $topics = $this->ai->suggestTopics($request->context ?? '', 6);
            return response()->json(['success' => true, 'topics' => $topics]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function aiTitles(Request $request): JsonResponse
    {
        $request->validate(['subject' => 'required|string|max:255']);

        try {
            $proposals = $this->ai->generateTitleAndExcerpt($request->subject);
            return response()->json(['success' => true, 'proposals' => $proposals]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // ── HELPERS PRIVÉS ────────────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════

    private function normalizeBooleans(Request $request): array
    {
        $normalized = [];

        foreach (['is_featured', 'is_pinned', 'ai_generated'] as $field) {
            if ($request->has($field)) {
                $val = $request->input($field);
                $normalized[$field] = in_array($val, ['true', '1', 'on', true, 1], true);
            }
        }

        // Tags envoyés en JSON string depuis Vue forceFormData
        if ($request->has('tags') && is_string($request->input('tags'))) {
            $decoded = json_decode($request->input('tags'), true);
            if (is_array($decoded)) {
                $normalized['tags'] = $decoded;
            }
        }

        return $normalized;
    }

    private function uniqueSlug(string $base): string
    {
        $slug  = $base;
        $count = 1;

        while (BlogPost::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $count++;
        }

        return $slug;
    }
}