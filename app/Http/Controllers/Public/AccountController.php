<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    // ── Dashboard client ──────────────────────────────────────────────
    public function dashboard(Request $request): Response
    {
        $user   = $request->user();
        $client = $user->client;

        if (! $client) {
            return Inertia::render('Public/Account/Dashboard', [
                'client'       => null,
                'stats'        => $this->emptyStats(),
                'recent_orders'=> [],
                'next_appointment' => null,
            ]);
        }

        // Stats globales
        $stats = [
            'total_orders'       => $client->orders()->count(),
            'total_spent'        => $client->orders()
                                          ->whereNotIn('status', ['cancelled'])
                                          ->sum('total'),
            'total_appointments' => $client->appointments()->count(),
            'upcoming_appointments' => $client->appointments()
                                              ->whereIn('status', ['pending', 'confirmed'])
                                              ->whereDate('date', '>=', today())
                                              ->count(),
            'loyalty_points'     => $client->loyalty_points ?? 0,
        ];;

        // Commandes récentes
        $recentOrders = $client->orders()
            ->with('items')
            ->latest()
            ->take(3)
            ->get()
            ->map(fn($o) => [
                'id'           => $o->id,
                'order_number' => $o->order_number,
                'status'       => $o->status->value,
                'status_label' => $o->status->label(),
                'status_color' => $o->status->color(),
                'total'        => $o->total,
                'items_count'  => $o->items->count(),
                'date'         => $o->created_at->locale('fr')->isoFormat('D MMM YYYY'),
            ]);

        // Prochain RDV
        $nextAppointment = $client->appointments()
            ->with('service')
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereDate('date', '>=', today())
            ->orderBy('date')
            ->orderBy('start_time')
            ->first();

        return Inertia::render('Public/Account/Dashboard', [
            'client' => [
                'id'          => $client->id,
                'full_name'   => $client->full_name,
                'first_name'  => $client->first_name,
                'email'       => $user->email,
                'phone'       => $client->phone,
                'is_vip'      => $client->is_vip,
                'avatar_url'  => $client->avatar_url,
                'member_since'=> $client->created_at->locale('fr')->isoFormat('MMMM YYYY'),
            ],
            'stats'        => $stats,
            'recent_orders'=> $recentOrders,
            'next_appointment' => $nextAppointment ? [
                'id'         => $nextAppointment->id,
                'reference'  => $nextAppointment->reference,
                'service'    => $nextAppointment->service->name,
                'date'       => $nextAppointment->date->locale('fr')->isoFormat('dddd D MMMM YYYY'),
                'start_time' => $nextAppointment->start_time,
                'status'     => $nextAppointment->status,
                'price'      => $nextAppointment->price,
                'deposit_paid' => $nextAppointment->deposit_paid,
            ] : null,
        ]);
    }

    // ── Mes commandes ─────────────────────────────────────────────────
    public function orders(Request $request): Response
    {
        $client = $request->user()->client;

        if (! $client) {
            return Inertia::render('Public/Account/Orders', ['orders' => [], 'stats' => $this->emptyStats()]);
        }

        $orders = $client->orders()
            ->with('items')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn($o) => [
                'id'           => $o->id,
                'order_number' => $o->order_number,
                'status'       => $o->status->value,
                'status_label' => $o->status->label(),
                'status_color' => $o->status->color(),
                'subtotal'     => $o->subtotal,
                'shipping_amount' => $o->shipping_amount,
                'total'        => $o->total,
                'is_paid'      => $o->is_paid,
                'tracking_number' => $o->tracking_number,
                'tracking_url' => $o->tracking_url,
                'shipped_at'   => $o->shipped_at?->locale('fr')->isoFormat('D MMM YYYY'),
                'date'         => $o->created_at->locale('fr')->isoFormat('D MMM YYYY'),
                'items_count'  => $o->items->count(),
                'items_preview'=> $o->items->take(2)->map(fn($i) => [
                    'name'      => $i->product_name,
                    'thumbnail' => $i->thumbnail ? Storage::url($i->thumbnail) : null,
                ]),
            ]);

        return Inertia::render('Public/Account/Orders', [
            'orders'  => $orders,
            'filters' => $request->only(['status']),
        ]);
    }

    // ── Détail commande ────────────────────────────────────────────────
    public function orderShow(Order $order): Response
    {
        // Sécurité : la commande doit appartenir au client connecté
        $client = auth()->user()->client;
        abort_if(! $client || $order->client_id !== $client->id, 403);

        $order->load(['items', 'invoice']);

        return Inertia::render('Public/Account/OrderShow', [
            'order' => [
                'id'               => $order->id,
                'order_number'     => $order->order_number,
                'status'           => $order->status->value,
                'status_label'     => $order->status->label(),
                'status_color'     => $order->status->color(),
                'subtotal'         => $order->subtotal,
                'discount_amount'  => $order->discount_amount,
                'shipping_amount'  => $order->shipping_amount,
                'tax_amount'       => $order->tax_amount,
                'total'            => $order->total,
                'is_paid'          => $order->is_paid,
                'shipping_address' => $order->shipping_address,
                'tracking_number'  => $order->tracking_number,
                'tracking_url'     => $order->tracking_url,
                'client_notes'     => $order->client_notes,
                'paid_at'          => $order->paid_at?->locale('fr')->isoFormat('D MMM YYYY'),
                'shipped_at'       => $order->shipped_at?->locale('fr')->isoFormat('D MMM YYYY'),
                'delivered_at'     => $order->delivered_at?->locale('fr')->isoFormat('D MMM YYYY'),
                'date'             => $order->created_at->locale('fr')->isoFormat('D MMMM YYYY à HH:mm'),
                'invoice_id'       => $order->invoice?->id,
                'items'            => $order->items->map(fn($i) => [
                    'product_name' => $i->product_name,
                    'product_sku'  => $i->product_sku,
                    'unit_price'   => $i->unit_price,
                    'quantity'     => $i->quantity,
                    'total'        => $i->total,
                    'thumbnail'    => $i->thumbnail ? Storage::url($i->thumbnail) : null,
                ]),
            ],
        ]);
    }

    // ── Mes rendez-vous ────────────────────────────────────────────────
    public function appointments(Request $request): Response
    {
        $client = $request->user()->client;

        if (! $client) {
            return Inertia::render('Public/Account/Appointments', ['upcoming' => [], 'past' => []]);
        }

        $upcoming = $client->appointments()
            ->with('service')
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereDate('date', '>=', today())
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->map(fn($a) => $this->mapAppointment($a));

        $past = $client->appointments()
            ->with('service')
            ->where(function ($q) {
                $q->whereDate('date', '<', today())
                  ->orWhereIn('status', ['completed', 'cancelled', 'no_show']);
            })
            ->orderByDesc('date')
            ->orderByDesc('start_time')
            ->take(10)
            ->get()
            ->map(fn($a) => $this->mapAppointment($a));

        return Inertia::render('Public/Account/Appointments', [
            'upcoming' => $upcoming,
            'past'     => $past,
        ]);
    }

    // ── Mes factures ──────────────────────────────────────────────────
    public function invoices(Request $request): Response
    {
        $client = $request->user()->client;

        if (! $client) {
            return Inertia::render('Public/Account/Invoices', ['invoices' => []]);
        }

        $invoices = $client->invoices()
            ->whereIn('status', ['sent', 'paid', 'overdue'])
            ->latest('issue_date')
            ->get()
            ->map(fn($i) => [
                'id'             => $i->id,
                'invoice_number' => $i->invoice_number,
                'status'         => $i->status->value,
                'status_label'   => $i->status->label(),
                'type'           => $i->type,
                'total'          => $i->total,
                'amount_due'     => $i->amount_due,
                'is_overdue'     => $i->is_overdue,
                'issue_date'     => $i->issue_date->locale('fr')->isoFormat('D MMM YYYY'),
                'due_date'       => $i->due_date->locale('fr')->isoFormat('D MMM YYYY'),
                'pdf_url'        => route('account.invoice.pdf', $i->id),
            ]);

        return Inertia::render('Public/Account/Invoices', [
            'invoices' => $invoices,
        ]);
    }

    // ── Télécharger PDF facture (client) ──────────────────────────────
    // public function downloadInvoice(Invoice $invoice)
    // {
    //     $client = auth()->user()->client;
    //     abort_if(! $client || $invoice->client_id !== $client->id, 403);

    //     // Rediriger vers le PDF admin (même route)
    //     return redirect()->route('admin.factures.pdf', $invoice->id);
    // }
    public function downloadInvoice(Invoice $invoice): \Illuminate\Http\Response
    {
        // Sécurité : la facture doit appartenir au client connecté
        $client = auth()->user()->client;
        abort_if(! $client || $invoice->client_id !== $client->id, 403);

        // Seules les factures envoyées ou payées sont accessibles
        abort_if(
            in_array($invoice->status->value, ['draft', 'cancelled']),
            403,
            'Cette facture n\'est pas encore disponible.'
        );

        $invoice->load(['client', 'items', 'payments']);

        $settings = array_merge(
            \App\Models\Setting::getGroup('general'),
            \App\Models\Setting::getGroup('invoice')
        );

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', [
            'invoice'  => $invoice,
            'settings' => $settings,
        ])->setPaper('A4', 'portrait');

        $filename = "facture-{$invoice->invoice_number}.pdf";

        return $pdf->stream($filename);
    }

    /* ── Helpers ─────────────────────────────────────────────────── */

    private function mapAppointment(Appointment $a): array
    {
        return [
            'id'           => $a->id,
            'reference'    => $a->reference,
            'service'      => [
                'name'          => $a->service->name,
                'duration'      => $a->service->duration_formatted,
                'image_url'     => $a->service->image_url,
                'category_label'=> $a->service->category->label(),
            ],
            'date'         => $a->date->locale('fr')->isoFormat('dddd D MMMM YYYY'),
            'date_short'   => $a->date->locale('fr')->isoFormat('D MMM'),
            'start_time'   => $a->start_time,
            'end_time'     => $a->end_time,
            'status'       => $a->status,
            'price'        => $a->price,
            'deposit_paid' => $a->deposit_paid,
            'deposit_amount'=> $a->deposit_amount,
            'client_notes' => $a->client_notes,
        ];
    }

    private function emptyStats(): array
    {
        return [
            'total_orders'          => 0,
            'total_spent'           => 0,
            'total_appointments'    => 0,
            'upcoming_appointments' => 0,
            'loyalty_points'        => 0,
        ];
    }
}