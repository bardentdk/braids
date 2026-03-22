<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Enums\OrderStatus;
use App\Enums\InvoiceStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Order::with(['client', 'items'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->whereHas('client', fn($cq) => $cq->search($request->search))
                ->orWhere('order_number', 'like', "%{$request->search}%")
            )
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest();

        $orders = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders->through(fn($o) => [
                'id'           => $o->id,
                'order_number' => $o->order_number,
                'status'       => $o->status->value,
                'status_label' => $o->status->label(),
                'status_color' => $o->status->color(),
                'subtotal'     => $o->subtotal,
                'total'        => $o->total,
                'items_count'  => $o->items->count(),
                'is_paid'      => $o->is_paid,
                'date'         => $o->created_at->locale('fr')->isoFormat('D MMM YYYY'),
                'client'       => ['id' => $o->client->id, 'full_name' => $o->client->full_name],
            ]),
            'filters'  => $request->only(['status', 'search', 'date_from', 'date_to']),
            'statuses' => collect(OrderStatus::cases())->map(fn($s) => [
                'value' => $s->value, 'label' => $s->label(),
            ]),
            'stats' => [
                'pending'    => Order::where('status', 'pending')->count(),
                'processing' => Order::where('status', 'processing')->count(),
                'shipped'    => Order::where('status', 'shipped')->count(),
                'total_month'=> Order::thisMonth()->sum('total'),
            ],
        ]);
    }

    public function show(Order $commande): Response
    {
        $commande->load(['client', 'items.product', 'invoice', 'coupon']);

        return Inertia::render('Admin/Orders/Show', [
            'order' => [
                'id'                => $commande->id,
                'order_number'      => $commande->order_number,
                'status'            => $commande->status->value,
                'status_label'      => $commande->status->label(),
                'status_color'      => $commande->status->color(),
                'subtotal'          => $commande->subtotal,
                'discount_amount'   => $commande->discount_amount,
                'shipping_amount'   => $commande->shipping_amount,
                'tax_rate'          => $commande->tax_rate,
                'tax_amount'        => $commande->tax_amount,
                'total'             => $commande->total,
                'is_paid'           => $commande->is_paid,
                'shipping_address'  => $commande->shipping_address,
                'shipping_method'   => $commande->shipping_method,
                'tracking_number'   => $commande->tracking_number,
                'tracking_url'      => $commande->tracking_url,
                'client_notes'      => $commande->client_notes,
                'admin_notes'       => $commande->admin_notes,
                'cancellation_reason' => $commande->cancellation_reason,
                'paid_at'           => $commande->paid_at?->locale('fr')->isoFormat('D MMM YYYY'),
                'shipped_at'        => $commande->shipped_at?->locale('fr')->isoFormat('D MMM YYYY'),
                'delivered_at'      => $commande->delivered_at?->locale('fr')->isoFormat('D MMM YYYY'),
                'date'              => $commande->created_at->locale('fr')->isoFormat('D MMMM YYYY à HH:mm'),
                'coupon'            => $commande->coupon ? ['code' => $commande->coupon->code] : null,
                'invoice'           => $commande->invoice ? [
                    'id'             => $commande->invoice->id,
                    'invoice_number' => $commande->invoice->invoice_number,
                    'status'         => $commande->invoice->status->value,
                ] : null,
                'client' => [
                    'id'        => $commande->client->id,
                    'full_name' => $commande->client->full_name,
                    'email'     => $commande->client->email,
                    'phone'     => $commande->client->phone,
                    'avatar_url'=> $commande->client->avatar_url,
                ],
                'items' => $commande->items->map(fn($item) => [
                    'id'           => $item->id,
                    'product_name' => $item->product_name,
                    'product_sku'  => $item->product_sku,
                    'unit_price'   => $item->unit_price,
                    'quantity'     => $item->quantity,
                    'total'        => $item->total,
                    'thumbnail'    => $item->thumbnail,
                    'options'      => $item->options,
                    'product_id'   => $item->product_id,
                ]),
            ],
            'statuses' => collect(OrderStatus::cases())->map(fn($s) => [
                'value' => $s->value, 'label' => $s->label(),
            ]),
        ]);
    }

    public function edit(Order $commande): Response
    {
        return Inertia::render('Admin/Orders/Edit', [
            'order' => $commande->load(['client', 'items']),
        ]);
    }

    public function update(Request $request, Order $commande): RedirectResponse
    {
        $request->validate([
            'admin_notes'    => 'nullable|string',
            'tracking_number'=> 'nullable|string|max:255',
            'tracking_url'   => 'nullable|url|max:500',
        ]);

        $commande->update($request->only(['admin_notes', 'tracking_number', 'tracking_url']));

        return back()->with('success', 'Commande mise à jour.');
    }

    public function destroy(Order $commande): RedirectResponse
    {
        if (! in_array($commande->status->value, ['cancelled', 'refunded'])) {
            return back()->with('error', 'Seules les commandes annulées peuvent être supprimées.');
        }

        $num = $commande->order_number;
        $commande->delete();

        return redirect()->route('admin.commandes.index')
                         ->with('success', "Commande {$num} supprimée.");
    }

    public function updateStatus(Request $request, Order $commande): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:' . implode(',', array_column(OrderStatus::cases(), 'value')),
            'reason' => 'nullable|string|max:500',
        ]);

        $updates = ['status' => $request->status];

        match($request->status) {
            'shipped'   => $updates['shipped_at']   = now(),
            'delivered' => $updates['delivered_at'] = now(),
            'cancelled' => array_merge($updates, ['cancelled_at' => now(), 'cancellation_reason' => $request->reason]),
            default     => null,
        };

        $commande->update($updates);

        return back()->with('success', 'Statut de la commande mis à jour.');
    }

    public function generateInvoice(Order $commande): RedirectResponse
    {
        if ($commande->invoice) {
            return redirect()->route('admin.factures.show', $commande->invoice)
                             ->with('info', 'Une facture existe déjà pour cette commande.');
        }

        $client   = $commande->client;
        $dueDate  = now()->addDays((int) \App\Models\Setting::get('invoice_due_days', 30));

        $invoice = Invoice::create([
            'client_id'       => $client->id,
            'order_id'        => $commande->id,
            'status'          => InvoiceStatus::Draft,
            'type'            => 'order',
            'client_snapshot' => [
                'name'        => $client->full_name,
                'email'       => $client->email,
                'phone'       => $client->phone,
                'address'     => $client->address,
                'city'        => $client->city,
                'postal_code' => $client->postal_code,
                'country'     => $client->country,
            ],
            'subtotal'        => $commande->subtotal,
            'discount_amount' => $commande->discount_amount,
            'tax_rate'        => $commande->tax_rate,
            'tax_amount'      => $commande->tax_amount,
            'total'           => $commande->total,
            'amount_paid'     => 0,
            'amount_due'      => $commande->total,
            'issue_date'      => today(),
            'due_date'        => $dueDate,
        ]);

        // Créer les lignes
        foreach ($commande->items as $item) {
            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'description' => $item->product_name,
                'unit_price'  => $item->unit_price,
                'quantity'    => $item->quantity,
                'total'       => $item->total,
            ]);
        }

        return redirect()->route('admin.factures.show', $invoice)
                         ->with('success', 'Facture générée avec succès.');
    }
}