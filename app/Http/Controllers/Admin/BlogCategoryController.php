<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BlogCategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Blog/Categories', [
            'categories' => BlogCategory::withCount(['posts', 'publishedPosts'])
                ->orderBy('sort_order')
                ->get()
                ->map(fn($c) => [
                    'id'                    => $c->id,
                    'name'                  => $c->name,
                    'slug'                  => $c->slug,
                    'description'           => $c->description,
                    'color'                 => $c->color,
                    'icon'                  => $c->icon,
                    'is_active'             => $c->is_active,
                    'sort_order'            => $c->sort_order,
                    'posts_count'           => $c->posts_count,
                    'published_posts_count' => $c->published_posts_count,
                    'cover_image_url'       => $c->cover_image_url,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'slug'             => 'nullable|string|max:100|unique:blog_categories,slug',
            'description'      => 'nullable|string|max:500',
            'color'            => 'nullable|string|max:20',
            'icon'             => 'nullable|string|max:50',
            'meta_title'       => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'sort_order'       => 'nullable|integer',
        ]);

        // ✅ empty() — gère le cas où la clé n'existe pas (slug nullable non envoyé)
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        BlogCategory::create($data);

        return back()->with('success', "Catégorie \"{$data['name']}\" créée.");
    }

    public function update(Request $request, BlogCategory $blogCategory): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'color'       => 'nullable|string|max:20',
            'icon'        => 'nullable|string|max:50',
            'sort_order'  => 'nullable|integer',
            'is_active'   => 'boolean',
        ]);

        $blogCategory->update($data);

        return back()->with('success', 'Catégorie mise à jour.');
    }

    public function destroy(BlogCategory $blogCategory): RedirectResponse
    {
        if ($blogCategory->posts()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer une catégorie contenant des articles.');
        }

        $name = $blogCategory->name;
        $blogCategory->delete();

        return back()->with('success', "Catégorie \"{$name}\" supprimée.");
    }
}