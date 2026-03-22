<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRequest;
use App\Models\Service;
use App\Enums\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(Request $request): Response
    {
        $services = Service::withCount(['appointments', 'reviews'])
            ->when($request->category, fn($q) => $q->byCategory($request->category))
            ->when($request->active !== null, fn($q) => $q->where('is_active', (bool) $request->active))
            ->orderBy('sort_order')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Admin/Services/Index', [
            'services' => $services->through(fn($s) => [
                'id'                 => $s->id,
                'name'               => $s->name,
                'category'           => $s->category->value,
                'category_label'     => $s->category->label(),
                'duration'           => $s->duration,
                'duration_formatted' => $s->duration_formatted,
                'price'              => $s->price,
                'deposit_amount'     => $s->deposit_amount,
                'deposit_required'   => $s->deposit_required,
                'is_active'          => $s->is_active,
                'is_featured'        => $s->is_featured,
                'image_url'          => $s->image_url,
                'appointments_count' => $s->appointments_count,
                'reviews_count'      => $s->reviews_count,
                'sort_order'         => $s->sort_order,
            ]),
            'filters'    => $request->only(['category', 'active']),
            'categories' => collect(ServiceCategory::cases())->map(fn($c) => [
                'value' => $c->value, 'label' => $c->label(), 'icon' => $c->icon(),
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Services/Create', [
            'categories' => collect(ServiceCategory::cases())->map(fn($c) => [
                'value' => $c->value, 'label' => $c->label(),
            ]),
        ]);
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('services', 'public');
        }

        $data['slug'] = Str::slug($data['name']);
        $service = Service::create($data);

        return redirect()->route('admin.services.show', $service)
                         ->with('success', "Service \"{$service->name}\" créé.");
    }

    public function show(Service $service): Response
    {
        $service->loadCount(['appointments', 'reviews']);
        $service->load(['reviews' => fn($q) => $q->approved()->latest()->take(5)]);

        // Stats du service
        $stats = [
            'total_revenue'      => $service->appointments()
                                            ->where('status', 'completed')
                                            ->sum('price'),
            'completion_rate'    => $service->appointments_count > 0
                ? round(($service->appointments()->where('status', 'completed')->count() / $service->appointments_count) * 100)
                : 0,
            'avg_rating'         => $service->reviews->avg('rating'),
            'upcoming'           => $service->appointments()->upcoming()->count(),
        ];

        return Inertia::render('Admin/Services/Show', [
            'service' => array_merge($service->toArray(), [
                'category_label'     => $service->category->label(),
                'duration_formatted' => $service->duration_formatted,
                'image_url'          => $service->image_url,
            ]),
            'stats' => $stats,
            'recent_reviews' => $service->reviews->map(fn($r) => [
                'id'     => $r->id,
                'rating' => $r->rating,
                'comment'=> \Str::limit($r->comment, 100),
                'date'   => $r->created_at->locale('fr')->isoFormat('D MMM YYYY'),
                'client' => ['name' => $r->client->full_name],
            ]),
        ]);
    }

    public function edit(Service $service): Response
    {
        return Inertia::render('Admin/Services/Edit', [
            'service'    => $service,
            'categories' => collect(ServiceCategory::cases())->map(fn($c) => [
                'value' => $c->value, 'label' => $c->label(),
            ]),
        ]);
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($service->image) Storage::disk('public')->delete($service->image);
            $data['image'] = $request->file('image')->store('services', 'public');
        }

        $data['slug'] = Str::slug($data['name']);
        $service->update($data);

        return redirect()->route('admin.services.show', $service)
                         ->with('success', 'Service mis à jour.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        if ($service->appointments()->whereNotIn('status', ['cancelled', 'completed'])->exists()) {
            return back()->with('error', 'Impossible de supprimer ce service : des rendez-vous actifs existent.');
        }

        if ($service->image) Storage::disk('public')->delete($service->image);
        $name = $service->name;
        $service->delete();

        return redirect()->route('admin.services.index')
                         ->with('success', "\"{$name}\" supprimé.");
    }

    public function toggle(Service $service): RedirectResponse
    {
        $service->update(['is_active' => ! $service->is_active]);
        $state = $service->is_active ? 'activé' : 'désactivé';

        return back()->with('success', "\"{$service->name}\" {$state}.");
    }
}