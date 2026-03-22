<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use App\Enums\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    public function index(Request $request): Response
    {
        $images = GalleryImage::withTrashed()
            ->when($request->category, fn($q) => $q->byCategory($request->category))
            ->when($request->featured, fn($q) => $q->featured())
            ->when($request->search, fn($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->orderBy('sort_order')->orderByDesc('created_at')
            ->paginate(20)->withQueryString();

        return Inertia::render('Admin/Gallery/Index', [
            'images' => $images->through(fn($img) => [
                'id'          => $img->id,
                'title'       => $img->title,
                'category'    => $img->category,
                'image_url'   => $img->image_url,
                'thumbnail_url'=> $img->thumbnail_url,
                'is_featured' => $img->is_featured,
                'is_active'   => $img->is_active,
                'sort_order'  => $img->sort_order,
                'views_count' => $img->views_count,
                'likes_count' => $img->likes_count,
                'tags'        => $img->tags,
                'deleted_at'  => $img->deleted_at,
            ]),
            'filters'    => $request->only(['category', 'featured', 'search']),
            'categories' => collect(ServiceCategory::cases())->map(fn($c) => [
                'value' => $c->value, 'label' => $c->label(),
            ]),
            'stats' => [
                'total'    => GalleryImage::count(),
                'featured' => GalleryImage::where('is_featured', true)->count(),
                'active'   => GalleryImage::where('is_active', true)->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Gallery/Create', [
            'categories' => collect(ServiceCategory::cases())->map(fn($c) => [
                'value' => $c->value, 'label' => $c->label(),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'images'      => 'required|array|min:1|max:20',
            'images.*'    => 'image|mimes:jpeg,jpg,png,webp|max:8192',
            'category'    => 'required|string',
            'title'       => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'tags'        => 'nullable|array',
            'is_featured' => 'boolean',
        ]);

        $maxOrder = GalleryImage::max('sort_order') ?? 0;

        foreach ($request->file('images') as $i => $image) {
            $path = $image->store('gallery', 'public');

            GalleryImage::create([
                'image'       => $path,
                'category'    => $request->category,
                'title'       => $request->title,
                'description' => $request->description,
                'tags'        => $request->tags,
                'is_featured' => $request->boolean('is_featured'),
                'is_active'   => true,
                'sort_order'  => $maxOrder + $i + 1,
            ]);
        }

        $count = count($request->file('images'));
        return redirect()->route('admin.galerie.index')
                         ->with('success', "{$count} image(s) ajoutée(s) à la galerie.");
    }

    public function show(GalleryImage $galerie): Response
    {
        return Inertia::render('Admin/Gallery/Show', [
            'image' => [
                ...$galerie->toArray(),
                'image_url'    => $galerie->image_url,
                'thumbnail_url'=> $galerie->thumbnail_url,
            ],
        ]);
    }

    public function edit(GalleryImage $galerie): Response
    {
        return Inertia::render('Admin/Gallery/Edit', [
            'image'      => $galerie,
            'categories' => collect(ServiceCategory::cases())->map(fn($c) => [
                'value' => $c->value, 'label' => $c->label(),
            ]),
        ]);
    }

    public function update(Request $request, GalleryImage $galerie): RedirectResponse
    {
        $request->validate([
            'title'       => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'category'    => 'required|string',
            'tags'        => 'nullable|array',
            'is_featured' => 'boolean',
            'is_active'   => 'boolean',
        ]);

        $galerie->update($request->only(['title', 'description', 'category', 'tags', 'is_featured', 'is_active']));

        return redirect()->route('admin.galerie.index')
                         ->with('success', 'Image mise à jour.');
    }

    public function destroy(GalleryImage $galerie): RedirectResponse
    {
        Storage::disk('public')->delete($galerie->image);
        if ($galerie->thumbnail) Storage::disk('public')->delete($galerie->thumbnail);
        $galerie->delete();

        return redirect()->route('admin.galerie.index')
                         ->with('success', 'Image supprimée.');
    }

    public function toggle(GalleryImage $galleryImage): RedirectResponse
    {
        $galleryImage->update(['is_active' => ! $galleryImage->is_active]);
        return back()->with('success', 'Visibilité mise à jour.');
    }

    public function feature(GalleryImage $galleryImage): RedirectResponse
    {
        $galleryImage->update(['is_featured' => ! $galleryImage->is_featured]);
        $state = $galleryImage->is_featured ? 'mise en avant' : 'retirée de la une';

        return back()->with('success', "Image {$state}.");
    }

    public function reorder(Request $request): RedirectResponse
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer']);

        foreach ($request->order as $index => $id) {
            GalleryImage::where('id', $id)->update(['sort_order' => $index]);
        }

        return back()->with('success', 'Ordre mis à jour.');
    }
}