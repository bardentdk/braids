<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use App\Models\Review;
use App\Models\Service;
use App\Models\Setting;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $featuredServices = Service::active()
            ->featured()
            ->with('reviews')
            ->take(6)
            ->get()
            ->map(fn($s) => [
                'id'                 => $s->id,
                'name'               => $s->name,
                'slug'               => $s->slug,
                'short_description'  => $s->short_description,
                'category'           => $s->category->value,
                'category_label'     => $s->category->label(),
                'duration_formatted' => $s->duration_formatted,
                'price'              => $s->price,
                'deposit_required'   => $s->deposit_required,
                'image_url'          => $s->image_url,
                'avg_rating'         => round($s->reviews->avg('rating'), 1),
                'reviews_count'      => $s->reviews->count(),
            ]);

        $galleryFeatured = GalleryImage::featured()
            ->orderBy('sort_order')
            ->take(8)
            ->get()
            ->map(fn($g) => [
                'id'            => $g->id,
                'title'         => $g->title,
                'category'      => $g->category,
                'image_url'     => $g->image_url,
                'thumbnail_url' => $g->thumbnail_url,
            ]);

        $reviews = Review::approved()
            ->featured()
            ->with('client', 'service')
            ->latest()
            ->take(6)
            ->get()
            ->map(fn($r) => [
                'id'      => $r->id,
                'rating'  => $r->rating,
                'title'   => $r->title,
                'comment' => $r->comment,
                'date'    => $r->created_at->locale('fr')->isoFormat('MMMM YYYY'),
                'client'  => [
                    'name'       => $r->client->first_name . ' ' . substr($r->client->last_name, 0, 1) . '.',
                    'avatar_url' => $r->client->avatar_url,
                    'hair_type'  => $r->client->hair_type,
                ],
                'service' => $r->service ? ['name' => $r->service->name] : null,
            ]);

        $stats = [
            'clients'      => \App\Models\Client::count(),
            'appointments' => \App\Models\Appointment::where('status', 'completed')->count(),
            'services'     => Service::active()->count(),
            'avg_rating'   => round(Review::approved()->avg('rating'), 1),
        ];

        $settings = [
            'site_tagline'    => Setting::get('site_tagline', 'L\'art des tresses, sublimé pour vous'),
            'site_description'=> Setting::get('site_description'),
            'site_phone'      => Setting::get('site_phone'),
            'site_email'      => Setting::get('site_email'),
            'site_address'    => Setting::get('site_address'),
            'social_instagram'=> Setting::get('social_instagram'),
            'social_tiktok'   => Setting::get('social_tiktok'),
            'social_facebook' => Setting::get('social_facebook'),
            'booking_enabled' => Setting::get('booking_enabled', true),
            'shop_enabled'    => Setting::get('shop_enabled', true),
        ];

        return Inertia::render('Public/Home', compact(
            'featuredServices', 'galleryFeatured', 'reviews', 'stats', 'settings'
        ));
    }
}