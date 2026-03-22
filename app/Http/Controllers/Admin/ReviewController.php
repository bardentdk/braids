<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $reviews = Review::with(['client', 'service', 'product'])
            ->when($request->status === 'pending',  fn($q) => $q->where('is_approved', false))
            ->when($request->status === 'approved', fn($q) => $q->where('is_approved', true))
            ->when($request->status === 'featured', fn($q) => $q->where('is_featured', true))
            ->when($request->rating, fn($q) => $q->where('rating', $request->rating))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $reviews->through(fn($r) => [
                'id'          => $r->id,
                'rating'      => $r->rating,
                'title'       => $r->title,
                'comment'     => \Str::limit($r->comment, 120),
                'is_approved' => $r->is_approved,
                'is_featured' => $r->is_featured,
                'has_reply'   => ! is_null($r->admin_reply),
                'date'        => $r->created_at->locale('fr')->isoFormat('D MMM YYYY'),
                'client'      => ['name' => $r->client->full_name],
                'subject'     => $r->service?->name ?? $r->product?->name ?? 'Général',
                'subject_type'=> $r->service_id ? 'service' : ($r->product_id ? 'product' : 'general'),
            ]),
            'filters' => $request->only(['status', 'rating']),
            'stats'   => [
                'pending'  => Review::where('is_approved', false)->count(),
                'approved' => Review::where('is_approved', true)->count(),
                'featured' => Review::where('is_featured', true)->count(),
                'avg'      => round(Review::where('is_approved', true)->avg('rating'), 1),
            ],
        ]);
    }

    public function show(Review $avi): Response
    {
        $avi->load(['client', 'service', 'product', 'appointment']);

        return Inertia::render('Admin/Reviews/Show', [
            'review' => [
                'id'            => $avi->id,
                'rating'        => $avi->rating,
                'title'         => $avi->title,
                'comment'       => $avi->comment,
                'images'        => $avi->images,
                'is_approved'   => $avi->is_approved,
                'is_featured'   => $avi->is_featured,
                'admin_reply'   => $avi->admin_reply,
                'replied_at'    => $avi->admin_replied_at?->locale('fr')->isoFormat('D MMM YYYY'),
                'date'          => $avi->created_at->locale('fr')->isoFormat('D MMMM YYYY'),
                'client'        => [
                    'id'        => $avi->client->id,
                    'full_name' => $avi->client->full_name,
                    'avatar_url'=> $avi->client->avatar_url,
                ],
                'service'  => $avi->service ? ['id' => $avi->service->id, 'name' => $avi->service->name] : null,
                'product'  => $avi->product ? ['id' => $avi->product->id, 'name' => $avi->product->name] : null,
            ],
        ]);
    }

    public function edit(Review $avi): Response
    {
        return Inertia::render('Admin/Reviews/Edit', [
            'review' => $avi->load(['client', 'service', 'product']),
        ]);
    }

    public function update(Request $request, Review $avi): RedirectResponse
    {
        $request->validate([
            'admin_reply' => 'nullable|string|max:1000',
        ]);

        $avi->update([
            'admin_reply'      => $request->admin_reply,
            'admin_replied_at' => $request->admin_reply ? now() : null,
        ]);

        return redirect()->route('admin.avis.show', $avi)
                         ->with('success', 'Réponse enregistrée.');
    }

    public function destroy(Review $avi): RedirectResponse
    {
        $avi->delete();
        return redirect()->route('admin.avis.index')->with('success', 'Avis supprimé.');
    }

    public function approve(Review $avi): RedirectResponse
    {
        $avi->update(['is_approved' => ! $avi->is_approved]);
        $state = $avi->is_approved ? 'approuvé' : 'masqué';

        return back()->with('success', "Avis {$state}.");
    }

    public function feature(Review $avi): RedirectResponse
    {
        if (! $avi->is_approved) {
            return back()->with('error', 'Approuvez l\'avis avant de le mettre en avant.');
        }

        $avi->update(['is_featured' => ! $avi->is_featured]);
        $state = $avi->is_featured ? 'mis en avant' : 'retiré de la une';

        return back()->with('success', "Avis {$state}.");
    }

    public function reply(Request $request, Review $avi): RedirectResponse
    {
        $request->validate(['reply' => 'required|string|max:1000']);

        $avi->update([
            'admin_reply'      => $request->reply,
            'admin_replied_at' => now(),
        ]);

        return back()->with('success', 'Réponse publiée.');
    }
}