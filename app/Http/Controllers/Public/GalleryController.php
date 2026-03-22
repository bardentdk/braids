<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use App\Enums\ServiceCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    public function index(Request $request): Response
    {
        $query = GalleryImage::active()
            ->when($request->category, fn($q) => $q->byCategory($request->category))
            ->orderBy('sort_order')
            ->orderByDesc('created_at');

        $images = $query->paginate(20)->withQueryString();

        $categories = collect(ServiceCategory::cases())
            ->map(fn($c) => [
                'value' => $c->value,
                'label' => $c->label(),
                'count' => GalleryImage::active()->byCategory($c->value)->count(),
            ])
            ->filter(fn($c) => $c['count'] > 0)
            ->values();

        return Inertia::render('Public/Gallery', [
            'images' => $images->through(fn($img) => [
                'id'            => $img->id,
                'title'         => $img->title,
                'description'   => $img->description,
                'category'      => $img->category,
                'image_url'     => $img->image_url,
                'thumbnail_url' => $img->thumbnail_url,
                'tags'          => $img->tags,
                'is_featured'   => $img->is_featured,
            ]),
            'categories' => $categories,
            'filters'    => $request->only(['category']),
            'total'      => GalleryImage::active()->count(),
        ]);
    }
}