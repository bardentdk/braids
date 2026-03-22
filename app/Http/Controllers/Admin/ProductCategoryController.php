<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductCategoryController extends Controller
{
    public function index(): Response
    {
        $categories = ProductCategory::withCount('activeProducts')
            ->orderBy('sort_order')
            ->get()
            ->map(fn($c) => [
                'id'                    => $c->id,
                'name'                  => $c->name,
                'slug'                  => $c->slug,
                'description'           => $c->description,
                'color'                 => $c->color,
                'image_url'             => $c->image_url,
                'is_active'             => $c->is_active,
                'sort_order'            => $c->sort_order,
                'active_products_count' => $c->active_products_count,
            ]);

        return Inertia::render('Admin/Categories/Index', ['categories' => $categories]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Categories/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'        => 'required|string|max:100|unique:product_categories,name',
            'description' => 'nullable|string|max:500',
            'color'       => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'image'       => 'nullable|image|mimes:jpeg,jpg,png,webp|max:3072',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'boolean',
        ]);

        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $data['slug'] = Str::slug($data['name']);
        ProductCategory::create($data);

        return redirect()->route('admin.categories.index')
                         ->with('success', "Catégorie \"{$data['name']}\" créée.");
    }

    public function edit(ProductCategory $category): Response
    {
        return Inertia::render('Admin/Categories/Edit', ['category' => $category]);
    }

    public function update(Request $request, ProductCategory $category): RedirectResponse
    {
        $request->validate([
            'name'        => "required|string|max:100|unique:product_categories,name,{$category->id}",
            'description' => 'nullable|string|max:500',
            'color'       => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'image'       => 'nullable|image|mimes:jpeg,jpg,png,webp|max:3072',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'boolean',
        ]);

        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($category->image) Storage::disk('public')->delete($category->image);
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $data['slug'] = Str::slug($data['name']);
        $category->update($data);

        return redirect()->route('admin.categories.index')
                         ->with('success', 'Catégorie mise à jour.');
    }

    public function destroy(ProductCategory $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'Impossible de supprimer : des produits existent dans cette catégorie.');
        }

        if ($category->image) Storage::disk('public')->delete($category->image);
        $name = $category->name;
        $category->delete();

        return redirect()->route('admin.categories.index')
                         ->with('success', "\"{$name}\" supprimée.");
    }

    public function show(ProductCategory $category): Response
    {
        return Inertia::render('Admin/Categories/Show', [
            'category' => $category->load('activeProducts'),
        ]);
    }
}