<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function show(Product $product): Response
    {
        abort_if(! $product->is_active, 404);

        // Incrémenter le compteur de vues
        $product->increment('views_count');

        $product->load(['category', 'images', 'reviews.client']);

        $related = Product::active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->inStock()
            ->take(4)
            ->get()
            ->map(fn($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'slug'        => $p->slug,
                'price'       => $p->price,
                'compare_price'=> $p->compare_price,
                'is_on_sale'  => $p->is_on_sale,
                'thumbnail_url'=> $p->thumbnail_url,
            ]);

        $reviews = $product->reviews()
            ->approved()
            ->with('client')
            ->latest()
            ->take(10)
            ->get()
            ->map(fn($r) => [
                'id'      => $r->id,
                'rating'  => $r->rating,
                'title'   => $r->title,
                'comment' => $r->comment,
                'date'    => $r->created_at->locale('fr')->isoFormat('D MMMM YYYY'),
                'has_reply'  => ! is_null($r->admin_reply),
                'admin_reply'=> $r->admin_reply,
                'client'  => [
                    'name'       => $r->client->first_name . ' ' . substr($r->client->last_name, 0, 1) . '.',
                    'avatar_url' => $r->client->avatar_url,
                ],
            ]);

        return Inertia::render('Public/Product', [
            'product' => [
                'id'               => $product->id,
                'name'             => $product->name,
                'slug'             => $product->slug,
                'short_description'=> $product->short_description,
                'description'      => $product->description,
                'price'            => $product->price,
                'compare_price'    => $product->compare_price,
                'is_on_sale'       => $product->is_on_sale,
                'discount_percent' => $product->discount_percent,
                'is_in_stock'      => $product->is_in_stock,
                'is_low_stock'     => $product->is_low_stock,
                'stock'            => $product->track_stock ? $product->stock : null,
                'weight'           => $product->weight,
                'tags'             => $product->tags,
                'attributes'       => $product->attributes,
                'thumbnail_url'    => $product->thumbnail_url,
                'category'         => ['name' => $product->category?->name, 'slug' => $product->category?->slug],
                'images'           => $product->images->map(fn($img) => [
                    'id'         => $img->id,
                    'url'        => $img->url,
                    'alt'        => $img->alt ?? $product->name,
                    'is_primary' => $img->is_primary,
                ]),
                'avg_rating'    => round($product->reviews->avg('rating'), 1),
                'reviews_count' => $product->reviews->count(),
            ],
            'reviews' => $reviews,
            'related' => $related,
        ]);
    }
}