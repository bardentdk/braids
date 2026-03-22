<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Product::with(['category', 'images' => fn($q) => $q->where('is_primary', true)])
            ->withCount('reviews')
            ->when($request->search, fn($q) => $q->search($request->search))
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->status === 'active', fn($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn($q) => $q->where('is_active', false))
            ->when($request->status === 'low_stock', fn($q) => $q->lowStock())
            ->when($request->status === 'out_of_stock', fn($q) =>
                $q->where('track_stock', true)->where('stock', '<=', 0)->where('allow_backorder', false)
            )
            ->when($request->featured, fn($q) => $q->where('is_featured', true))
            ->orderByDesc('created_at');

        $products = $query->paginate(16)->withQueryString();

        return Inertia::render('Admin/Products/Index', [
            'products' => $products->through(fn($p) => [
                'id'              => $p->id,
                'name'            => $p->name,
                'slug'            => $p->slug,
                'sku'             => $p->sku,
                'price'           => $p->price,
                'compare_price'   => $p->compare_price,
                'stock'           => $p->stock,
                'is_active'       => $p->is_active,
                'is_featured'     => $p->is_featured,
                'is_on_sale'      => $p->is_on_sale,
                'discount_percent'=> $p->discount_percent,
                'is_in_stock'     => $p->is_in_stock,
                'is_low_stock'    => $p->is_low_stock,
                'thumbnail_url'   => $p->thumbnail_url,
                'reviews_count'   => $p->reviews_count,
                'sales_count'     => $p->sales_count,
                'category'        => ['id' => $p->category?->id, 'name' => $p->category?->name],
            ]),
            'filters'    => $request->only(['search', 'category_id', 'status', 'featured']),
            'categories' => ProductCategory::active()->get(['id', 'name']),
            'stats' => [
                'total'      => Product::count(),
                'active'     => Product::where('is_active', true)->count(),
                'low_stock'  => Product::lowStock()->count(),
                'out_of_stock'=> Product::where('track_stock', true)->where('stock', '<=', 0)->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Products/Create', [
            'categories' => ProductCategory::active()->get(['id', 'name']),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Thumbnail principale
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('products/thumbnails', 'public');
        }

        $data['slug'] = Str::slug($data['name']);

        $product = Product::create($data);

        // Images supplémentaires
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $i => $image) {
                $path = $image->store('products/images', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'path'       => $path,
                    'is_primary' => $i === 0 && ! $request->hasFile('thumbnail'),
                    'sort_order' => $i,
                ]);
            }
        }

        return redirect()->route('admin.produits.show', $product)
                         ->with('success', "Produit \"{$product->name}\" créé avec succès.");
    }

    public function show(Product $produit): Response
    {
        $produit->load(['category', 'images', 'reviews.client']);

        return Inertia::render('Admin/Products/Show', [
            'product' => [
                'id'              => $produit->id,
                'name'            => $produit->name,
                'slug'            => $produit->slug,
                'sku'             => $produit->sku,
                'short_description'=> $produit->short_description,
                'description'     => $produit->description,
                'price'           => $produit->price,
                'compare_price'   => $produit->compare_price,
                'cost_price'      => $produit->cost_price,
                'margin'          => $produit->margin,
                'stock'           => $produit->stock,
                'low_stock_alert' => $produit->low_stock_alert,
                'track_stock'     => $produit->track_stock,
                'allow_backorder' => $produit->allow_backorder,
                'weight'          => $produit->weight,
                'dimensions'      => $produit->dimensions,
                'is_active'       => $produit->is_active,
                'is_featured'     => $produit->is_featured,
                'is_digital'      => $produit->is_digital,
                'is_on_sale'      => $produit->is_on_sale,
                'discount_percent'=> $produit->discount_percent,
                'thumbnail_url'   => $produit->thumbnail_url,
                'tags'            => $produit->tags,
                'attributes'      => $produit->attributes,
                'sales_count'     => $produit->sales_count,
                'views_count'     => $produit->views_count,
                'category'        => $produit->category,
                'images'          => $produit->images->map(fn($img) => [
                    'id'         => $img->id,
                    'url'        => $img->url,
                    'alt'        => $img->alt,
                    'is_primary' => $img->is_primary,
                    'sort_order' => $img->sort_order,
                ]),
                'reviews'         => $produit->reviews->map(fn($r) => [
                    'id'         => $r->id,
                    'rating'     => $r->rating,
                    'title'      => $r->title,
                    'comment'    => $r->comment,
                    'is_approved'=> $r->is_approved,
                    'date'       => $r->created_at->locale('fr')->isoFormat('D MMM YYYY'),
                    'client'     => ['name' => $r->client->full_name],
                ]),
                'avg_rating'      => $produit->reviews->avg('rating'),
            ],
        ]);
    }

    public function edit(Product $produit): Response
    {
        return Inertia::render('Admin/Products/Edit', [
            'product'    => $produit->load('images'),
            'categories' => ProductCategory::active()->get(['id', 'name']),
        ]);
    }

    public function update(ProductRequest $request, Product $produit): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('thumbnail')) {
            if ($produit->thumbnail) Storage::disk('public')->delete($produit->thumbnail);
            $data['thumbnail'] = $request->file('thumbnail')->store('products/thumbnails', 'public');
        }

        $data['slug'] = Str::slug($data['name']);
        $produit->update($data);

        return redirect()->route('admin.produits.show', $produit)
                         ->with('success', 'Produit mis à jour.');
    }

    public function destroy(Product $produit): RedirectResponse
    {
        // Supprimer les fichiers
        if ($produit->thumbnail) Storage::disk('public')->delete($produit->thumbnail);
        foreach ($produit->images as $img) {
            Storage::disk('public')->delete($img->path);
        }

        $name = $produit->name;
        $produit->delete();

        return redirect()->route('admin.produits.index')
                         ->with('success', "\"{$name}\" supprimé.");
    }

    public function uploadImages(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'images'   => 'required|array|max:10',
            'images.*' => 'image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $currentMax = $product->images()->max('sort_order') ?? -1;

        foreach ($request->file('images') as $i => $image) {
            $path = $image->store('products/images', 'public');
            ProductImage::create([
                'product_id' => $product->id,
                'path'       => $path,
                'is_primary' => false,
                'sort_order' => $currentMax + $i + 1,
            ]);
        }

        return back()->with('success', 'Images ajoutées.');
    }

    public function deleteImage(Product $product, ProductImage $image): RedirectResponse
    {
        Storage::disk('public')->delete($image->path);
        $wasPrimary = $image->is_primary;
        $image->delete();

        if ($wasPrimary) {
            $product->images()->first()?->update(['is_primary' => true]);
        }

        return back()->with('success', 'Image supprimée.');
    }

    public function setPrimaryImage(Product $product, ProductImage $image): RedirectResponse
    {
        $product->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        return back()->with('success', 'Image principale définie.');
    }

    public function toggle(Product $produit): RedirectResponse
    {
        $produit->update(['is_active' => ! $produit->is_active]);
        $state = $produit->is_active ? 'activé' : 'désactivé';

        return back()->with('success', "\"{$produit->name}\" {$state}.");
    }
}