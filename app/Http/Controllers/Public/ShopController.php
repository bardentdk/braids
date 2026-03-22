<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Product::active()
            ->inStock()
            ->with(['category', 'images' => fn($q) => $q->where('is_primary', true)])
            ->when($request->category, fn($q) => $q->whereHas('category', fn($cq) =>
                $cq->where('slug', $request->category)
            ))
            ->when($request->search, fn($q) => $q->search($request->search))
            ->when($request->sort === 'price_asc',  fn($q) => $q->orderBy('price'))
            ->when($request->sort === 'price_desc', fn($q) => $q->orderByDesc('price'))
            ->when($request->sort === 'popular',    fn($q) => $q->orderByDesc('sales_count'))
            ->when($request->featured, fn($q) => $q->featured())
            ->when(! $request->sort || $request->sort === 'latest', fn($q) => $q->orderByDesc('created_at'));

        $products = $query->paginate(12)->withQueryString();

        return Inertia::render('Public/Shop', [
            'products' => $products->through(fn($p) => [
                'id'              => $p->id,
                'name'            => $p->name,
                'slug'            => $p->slug,
                'short_description'=> $p->short_description,
                'price'           => $p->price,
                'compare_price'   => $p->compare_price,
                'is_on_sale'      => $p->is_on_sale,
                'discount_percent'=> $p->discount_percent,
                'is_in_stock'     => $p->is_in_stock,
                'is_low_stock'    => $p->is_low_stock,
                'stock'           => $p->track_stock ? $p->stock : null,
                'thumbnail_url'   => $p->thumbnail_url,
                'is_featured'     => $p->is_featured,
                'category'        => ['name' => $p->category?->name, 'slug' => $p->category?->slug],
            ]),
            'categories' => ProductCategory::active()
                ->withCount(['activeProducts'])
                ->get()
                ->map(fn($c) => [
                    'id'             => $c->id,
                    'name'           => $c->name,
                    'slug'           => $c->slug,
                    'color'          => $c->color,
                    'products_count' => $c->active_products_count,
                ]),
            'filters'  => $request->only(['category', 'search', 'sort', 'featured']),
            'settings' => [
                'free_shipping_at' => Setting::get('shop_free_shipping_at', 75),
                'shipping_cost'    => Setting::get('shop_shipping_cost', 4.90),
            ],
        ]);
    }
}