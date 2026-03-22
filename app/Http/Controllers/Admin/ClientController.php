<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ClientRequest;
use App\Models\Client;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Client::with('user')
            ->withCount(['appointments', 'orders'])
            ->when($request->search, fn($q) => $q->search($request->search))
            ->when($request->vip, fn($q) => $q->vip())
            ->when($request->hair_type, fn($q) => $q->where('hair_type', $request->hair_type))
            ->when($request->sort === 'spent', fn($q) => $q->orderByRaw(
                '(SELECT COALESCE(SUM(total),0) FROM orders WHERE orders.client_id = clients.id AND orders.status != "cancelled") DESC'
            ))
            ->when(! $request->sort || $request->sort === 'recent', fn($q) => $q->latest());

        $clients = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Clients/Index', [
            'clients' => $clients->through(fn($c) => [
                'id'                 => $c->id,
                'full_name'          => $c->full_name,
                'first_name'         => $c->first_name,
                'last_name'          => $c->last_name,
                'email'              => $c->email,
                'phone'              => $c->phone,
                'avatar_url'         => $c->avatar_url,
                'is_vip'             => $c->is_vip,
                'hair_type'          => $c->hair_type,
                'loyalty_points'     => $c->loyalty_points,
                'appointments_count' => $c->appointments_count,
                'orders_count'       => $c->orders_count,
                'total_spent'        => $c->total_spent,
                'created_at'         => $c->created_at->locale('fr')->isoFormat('D MMM YYYY'),
            ]),
            'filters' => $request->only(['search', 'vip', 'hair_type', 'sort']),
            'stats' => [
                'total'     => Client::count(),
                'vip'       => Client::vip()->count(),
                'new_month' => Client::whereMonth('created_at', now()->month)->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Clients/Create');
    }

    public function store(ClientRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Créer un compte User si souhaité
        $user = null;
        if ($request->boolean('create_account') && $data['email']) {
            $user = User::create([
                'name'              => $data['first_name'] . ' ' . $data['last_name'],
                'email'             => $data['email'],
                'password'          => Hash::make($data['password'] ?? \Str::random(12)),
                'role'              => UserRole::Client,
                'email_verified_at' => now(),
            ]);
        }

        $client = Client::create([
            ...$data,
            'user_id' => $user?->id,
        ]);

        return redirect()->route('admin.clients.show', $client)
                         ->with('success', "Client {$client->full_name} créé avec succès.");
    }

    public function show(Client $client): Response
    {
        $client->load(['appointments.service', 'orders.items', 'reviews', 'invoices', 'payments']);

        return Inertia::render('Admin/Clients/Show', [
            'client' => [
                'id'             => $client->id,
                'full_name'      => $client->full_name,
                'first_name'     => $client->first_name,
                'last_name'      => $client->last_name,
                'email'          => $client->email,
                'phone'          => $client->phone,
                'address'        => $client->address,
                'city'           => $client->city,
                'postal_code'    => $client->postal_code,
                'country'        => $client->country,
                'birth_date'     => $client->birth_date?->locale('fr')->isoFormat('D MMMM YYYY'),
                'hair_type'      => $client->hair_type,
                'allergies'      => $client->allergies,
                'notes'          => $client->notes,
                'is_vip'         => $client->is_vip,
                'loyalty_points' => $client->loyalty_points,
                'newsletter'     => $client->newsletter,
                'source'         => $client->source,
                'avatar_url'     => $client->avatar_url,
                'total_spent'    => $client->total_spent,
                'created_at'     => $client->created_at->locale('fr')->isoFormat('D MMMM YYYY'),
                'has_account'    => ! is_null($client->user_id),
            ],
            'appointments' => $client->appointments->map(fn($a) => [
                'id'         => $a->id,
                'reference'  => $a->reference,
                'date'       => $a->date->locale('fr')->isoFormat('D MMM YYYY'),
                'start_time' => $a->start_time_formatted,
                'status'     => $a->status->value,
                'status_label'=> $a->status->label(),
                'status_color'=> $a->status->color(),
                'price'      => $a->price,
                'service'    => ['name' => $a->service->name],
            ])->sortByDesc('date')->values(),
            'orders' => $client->orders->map(fn($o) => [
                'id'           => $o->id,
                'order_number' => $o->order_number,
                'total'        => $o->total,
                'status'       => $o->status->value,
                'status_label' => $o->status->label(),
                'status_color' => $o->status->color(),
                'items_count'  => $o->items->count(),
                'date'         => $o->created_at->locale('fr')->isoFormat('D MMM YYYY'),
            ])->sortByDesc('created_at')->values(),
            'stats' => [
                'total_appointments' => $client->appointments->count(),
                'completed_appointments' => $client->appointments->where('status.value', 'completed')->count(),
                'total_orders'       => $client->orders->count(),
                'total_spent'        => $client->total_spent,
                'avg_rating'         => $client->reviews->avg('rating'),
            ],
        ]);
    }

    public function edit(Client $client): Response
    {
        return Inertia::render('Admin/Clients/Edit', [
            'client' => $client,
        ]);
    }

    public function update(ClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        return redirect()->route('admin.clients.show', $client)
                         ->with('success', 'Profil client mis à jour.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $name = $client->full_name;
        $client->delete();

        return redirect()->route('admin.clients.index')
                         ->with('success', "{$name} a été supprimé.");
    }

    public function history(Client $client): Response
    {
        // Historique complet fusionné RDV + Commandes
        $history = collect();

        $client->appointments()->with('service')->get()->each(function ($a) use (&$history) {
            $history->push([
                'type'   => 'appointment',
                'date'   => $a->date,
                'label'  => $a->service->name,
                'amount' => $a->price,
                'status' => $a->status->value,
                'ref'    => $a->reference,
                'id'     => $a->id,
            ]);
        });

        $client->orders()->with('items')->get()->each(function ($o) use (&$history) {
            $history->push([
                'type'   => 'order',
                'date'   => $o->created_at->toDateString(),
                'label'  => $o->items->count() . ' article(s)',
                'amount' => $o->total,
                'status' => $o->status->value,
                'ref'    => $o->order_number,
                'id'     => $o->id,
            ]);
        });

        return Inertia::render('Admin/Clients/History', [
            'client'  => ['id' => $client->id, 'full_name' => $client->full_name],
            'history' => $history->sortByDesc('date')->values(),
        ]);
    }

    public function toggleVip(Client $client): RedirectResponse
    {
        $client->update(['is_vip' => ! $client->is_vip]);
        $label = $client->is_vip ? 'ajouté aux clients VIP' : 'retiré des clients VIP';

        return back()->with('success', "{$client->full_name} a été {$label}.");
    }
}