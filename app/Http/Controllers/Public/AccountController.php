<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    // ── Dashboard ─────────────────────────────────────────────────────
    public function dashboard(): Response
    {
        $user   = auth()->user();
        $client = $user->client;

        $nextAppointment = $client
            ? Appointment::where('client_id', $client->id)
                ->where('date', '>=', today())
                ->whereIn('status', ['pending', 'confirmed'])
                ->orderBy('date')->orderBy('start_time')
                ->with('service')->first()
            : null;

        $recentOrders = $client
            ? Order::where('client_id', $client->id)->latest()->take(3)->get()
                ->map(fn($o) => [
                    'id'           => $o->id,
                    'order_number' => $o->order_number,
                    'total'        => $o->total,
                    'status'       => $o->status,
                    'date'         => $o->created_at->locale('fr')->isoFormat('D MMM YYYY'),
                ])
            : collect();

        return Inertia::render('Public/Account/Dashboard', [
            'stats' => [
                'total_appointments' => $client ? Appointment::where('client_id', $client->id)->count() : 0,
                'total_orders'       => $client ? Order::where('client_id', $client->id)->count() : 0,
                'total_spent'        => $client ? Order::where('client_id', $client->id)->where('payment_status', 'paid')->sum('total') : 0,
            ],
            'next_appointment' => $nextAppointment ? [
                'id'           => $nextAppointment->id,
                'service_name' => $nextAppointment->service?->name,
                'date'         => Carbon::parse($nextAppointment->date)->locale('fr')->isoFormat('dddd D MMMM YYYY'),
                'time_start'   => substr($nextAppointment->start_time, 0, 5),
                'status'       => $nextAppointment->status instanceof \BackedEnum
                    ? $nextAppointment->status->value
                    : (string) $nextAppointment->status,
            ] : null,
            'recent_orders' => $recentOrders,
            'client' => $client ? [
                'is_vip'      => $client->is_vip,
                'loyalty_pts' => $client->loyalty_points ?? 0,
            ] : null,
        ]);
    }

    // ── Commandes ─────────────────────────────────────────────────────
    public function orders(): Response
    {
        $client = auth()->user()->client;

        $orders = $client
            ? Order::where('client_id', $client->id)->with('items')->latest()
                ->paginate(10)->through(fn($o) => [
                    'id'             => $o->id,
                    'order_number'   => $o->order_number,
                    'total'          => $o->total,
                    'subtotal'       => $o->subtotal,
                    'shipping_amount'=> $o->shipping_amount ?? 0,
                    'discount_amount'=> $o->discount_amount ?? 0,
                    'status'         => $o->status,
                    'payment_status' => $o->payment_status,
                    'invoice_id'     => $o->invoice_id ?? null,
                    'date'           => $o->created_at->locale('fr')->isoFormat('D MMMM YYYY'),
                    'items'          => $o->items->map(fn($i) => [
                        'product_name' => $i->product_name,
                        'quantity'     => $i->quantity,
                        'unit_price'   => $i->unit_price,
                        'total'        => $i->total,
                    ]),
                ])
            : collect();

        return Inertia::render('Public/Account/Orders', ['orders' => $orders]);
    }

    // ── Détail commande ───────────────────────────────────────────────
    public function orderShow(Order $order): Response
    {
        $client = auth()->user()->client;
        abort_if(! $client || $order->client_id !== $client->id, 403);
        $order->load('items');

        return Inertia::render('Public/Account/OrderShow', [
            'order' => [
                'id'               => $order->id,
                'order_number'     => $order->order_number,
                'status'           => $order->status,
                'status_label'     => ucfirst($order->status),
                'status_color'     => $this->orderStatusColor($order->status),
                'payment_status'   => $order->payment_status,
                'date'             => $order->created_at->locale('fr')->isoFormat('D MMMM YYYY'),
                'paid_at'          => $order->paid_at?->locale('fr')->isoFormat('D MMMM YYYY'),
                'shipped_at'       => $order->shipped_at?->locale('fr')->isoFormat('D MMMM YYYY'),
                'delivered_at'     => $order->delivered_at?->locale('fr')->isoFormat('D MMMM YYYY'),
                'subtotal'         => $order->subtotal,
                'total'            => $order->total,
                'shipping_amount'  => $order->shipping_amount ?? 0,
                'discount_amount'  => $order->discount_amount ?? 0,
                'tracking_number'  => $order->tracking_number ?? null,
                'tracking_url'     => $order->tracking_url ?? null,
                'invoice_id'       => $order->invoice_id ?? null,
                'client_notes'     => $order->client_notes ?? null,
                'shipping_address' => $order->shipping_address,
                'items'            => $order->items->map(fn($i) => [
                    'product_name' => $i->product_name,
                    'product_sku'  => $i->product_sku ?? null,
                    'quantity'     => $i->quantity,
                    'unit_price'   => $i->unit_price,
                    'total'        => $i->total,
                    'thumbnail'    => $i->thumbnail ?? null,
                ]),
            ],
        ]);
    }

    // ── Rendez-vous ───────────────────────────────────────────────────
    public function appointments(): Response
    {
        $client = auth()->user()->client;

        $upcoming = $client
            ? Appointment::where('client_id', $client->id)
                ->where('date', '>=', today())
                ->whereIn('status', ['pending', 'confirmed'])
                ->orderBy('date')->orderBy('start_time')
                ->with('service')->get()
                ->map(fn($a) => $this->mapAppointment($a))
            : collect();

        $past = $client
            ? Appointment::where('client_id', $client->id)
                ->where(fn($q) =>
                    $q->where('date', '<', today())
                      ->orWhereIn('status', ['completed', 'cancelled', 'no_show'])
                )
                ->orderByDesc('date')->orderByDesc('start_time')
                ->with('service')->take(20)->get()
                ->map(fn($a) => $this->mapAppointment($a))
            : collect();

        return Inertia::render('Public/Account/Appointments', [
            'upcoming' => $upcoming,
            'past'     => $past,
        ]);
    }

    // ── Factures ─────────────────────────────────────────────────────
    public function invoices(): Response
    {
        $client = auth()->user()->client;

        $invoices = $client
            ? Invoice::where('client_id', $client->id)
                ->whereIn('status', ['sent', 'paid'])->latest()
                ->paginate(10)->through(fn($i) => [
                    'id'       => $i->id,
                    'number'   => $i->invoice_number,
                    'total'    => $i->total,
                    'status'   => $i->status,
                    'date'     => $i->created_at->locale('fr')->isoFormat('D MMMM YYYY'),
                    'due_date' => $i->due_date?->locale('fr')->isoFormat('D MMMM YYYY'),
                    'paid_at'  => $i->paid_at?->locale('fr')->isoFormat('D MMMM YYYY'),
                ])
            : collect();

        return Inertia::render('Public/Account/Invoices', ['invoices' => $invoices]);
    }

    // ── Télécharger une facture ───────────────────────────────────────
    public function downloadInvoice(Invoice $invoice): HttpResponse
    {
        $client = auth()->user()->client;
        abort_if(! $client || $invoice->client_id !== $client->id, 403);
        $invoice->load(['items', 'client.user']);
        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice])->setPaper('a4');
        return $pdf->download("facture-{$invoice->invoice_number}.pdf");
    }

    // ── Helpers ───────────────────────────────────────────────────────
    private function mapAppointment(Appointment $a): array
    {
        $status = $a->status instanceof \BackedEnum ? $a->status->value : (string) $a->status;
        return [
            'id'           => $a->id,
            'reference'    => $a->reference ?? '#' . $a->id,
            'service_name' => $a->service?->name ?? '—',
            'service_image'=> $a->service?->image_url ?? null,
            'date'         => Carbon::parse($a->date)->locale('fr')->isoFormat('dddd D MMMM YYYY'),
            'date_raw'     => $a->date,
            'time_start'   => substr($a->start_time, 0, 5),
            'time_end'     => substr($a->end_time,   0, 5),
            'duration'     => $a->service?->duration ?? 60,
            'price'        => $a->price,
            'deposit_paid' => $a->deposit_paid ?? false,
            'status'       => $status,
            'status_label' => $this->appointmentStatusLabel($status),
            'status_color' => $this->appointmentStatusColor($status),
            'notes'        => $a->client_notes ?? null,
        ];
    }

    private function appointmentStatusLabel(string $status): string
    {
        return match($status) {
            'pending'   => 'En attente de confirmation',
            'confirmed' => 'Confirmé',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé',
            'no_show'   => 'Absent',
            default     => ucfirst($status),
        };
    }

    private function appointmentStatusColor(string $status): string
    {
        return match($status) {
            'pending'   => '#d97706',
            'confirmed' => '#059669',
            'completed' => '#6366f1',
            'cancelled' => '#dc2626',
            default     => '#6b7280',
        };
    }

    private function orderStatusColor(string $status): string
    {
        return match($status) {
            'pending'    => '#d97706',
            'processing' => '#6366f1',
            'shipped'    => '#3b82f6',
            'delivered'  => '#059669',
            'cancelled'  => '#dc2626',
            default      => '#6b7280',
        };
    }
}