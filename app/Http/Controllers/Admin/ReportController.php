<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $year = $request->year ?? now()->year;

        // ── CA mensuel sur l'année ─────────────────────────────
        $monthlyRevenue = collect(range(1, 12))->map(function ($month) use ($year) {
            $revenue = Payment::where('status', 'completed')
                              ->whereYear('paid_at', $year)
                              ->whereMonth('paid_at', $month)
                              ->sum('amount');
            return [
                'month'   => Carbon::create($year, $month)->locale('fr')->isoFormat('MMMM'),
                'short'   => Carbon::create($year, $month)->locale('fr')->isoFormat('MMM'),
                'revenue' => round($revenue, 2),
            ];
        });

        // ── KPIs annuels ───────────────────────────────────────
        $annualStats = [
            'total_revenue'        => Payment::where('status', 'completed')->whereYear('paid_at', $year)->sum('amount'),
            'total_appointments'   => Appointment::whereYear('created_at', $year)->count(),
            'completed_appointments'=> Appointment::where('status', 'completed')->whereYear('created_at', $year)->count(),
            'total_orders'         => Order::whereYear('created_at', $year)->count(),
            'new_clients'          => Client::whereYear('created_at', $year)->count(),
            'avg_order_value'      => Order::whereYear('created_at', $year)->whereNotIn('status', ['cancelled'])->avg('total'),
        ];

        // ── Top services ───────────────────────────────────────
        $topServices = Service::withCount(['appointments as count' => fn($q) =>
            $q->where('status', 'completed')->whereYear('created_at', $year)
        ])
        ->withSum(['appointments as revenue' => fn($q) =>
            $q->where('status', 'completed')->whereYear('created_at', $year)
        ], 'price')
        ->orderByDesc('revenue')
        ->take(6)
        ->get()
        ->map(fn($s) => [
            'name'    => $s->name,
            'count'   => $s->count,
            'revenue' => round($s->revenue ?? 0, 2),
        ]);

        // ── Top produits ───────────────────────────────────────
        $topProducts = Product::withSum(['orderItems as revenue' => fn($q) =>
            $q->whereHas('order', fn($oq) => $oq->whereYear('created_at', $year)->whereNotIn('status', ['cancelled']))
        ], 'total')
        ->withSum(['orderItems as units' => fn($q) =>
            $q->whereHas('order', fn($oq) => $oq->whereYear('created_at', $year)->whereNotIn('status', ['cancelled']))
        ], 'quantity')
        ->orderByDesc('revenue')
        ->take(5)
        ->get()
        ->map(fn($p) => [
            'name'    => $p->name,
            'units'   => (int) ($p->units ?? 0),
            'revenue' => round($p->revenue ?? 0, 2),
        ]);

        // ── Répartition par méthode de paiement ───────────────
        $paymentMethods = Payment::where('status', 'completed')
            ->whereYear('paid_at', $year)
            ->selectRaw('method, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('method')
            ->get()
            ->map(fn($p) => [
                'method' => $p->method instanceof \App\Enums\PaymentMethod
                    ? $p->method->label()
                    : $p->method,
                'total'  => round($p->total, 2),
                'count'  => $p->count,
            ]);

        // ── Acquisition clients ────────────────────────────────
        $clientAcquisition = collect(range(1, 12))->map(fn($m) => [
            'month' => Carbon::create($year, $m)->locale('fr')->isoFormat('MMM'),
            'count' => Client::whereYear('created_at', $year)->whereMonth('created_at', $m)->count(),
        ]);

        // ── Taux d'occupation (RDV) ────────────────────────────
        $occupancyRate = collect(range(1, 12))->map(function ($m) use ($year) {
            $total     = Appointment::whereYear('date', $year)->whereMonth('date', $m)->count();
            $completed = Appointment::where('status', 'completed')->whereYear('date', $year)->whereMonth('date', $m)->count();
            $cancelled = Appointment::where('status', 'cancelled')->whereYear('date', $year)->whereMonth('date', $m)->count();

            return [
                'month'     => Carbon::create($year, $m)->locale('fr')->isoFormat('MMM'),
                'total'     => $total,
                'completed' => $completed,
                'cancelled' => $cancelled,
                'rate'      => $total > 0 ? round($completed / $total * 100) : 0,
            ];
        });

        return Inertia::render('Admin/Reports/Index', [
            'year'              => (int) $year,
            'availableYears'    => $this->getAvailableYears(),
            'monthlyRevenue'    => $monthlyRevenue,
            'annualStats'       => $annualStats,
            'topServices'       => $topServices,
            'topProducts'       => $topProducts,
            'paymentMethods'    => $paymentMethods,
            'clientAcquisition' => $clientAcquisition,
            'occupancyRate'     => $occupancyRate,
        ]);
    }

    public function sales(Request $request): Response
    {
        $from = $request->from ? Carbon::parse($request->from) : now()->startOfMonth();
        $to   = $request->to   ? Carbon::parse($request->to)   : now()->endOfMonth();

        $orders = Order::with(['client', 'items'])
            ->whereNotIn('status', ['cancelled'])
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->get();

        $appointments = Appointment::with(['client', 'service'])
            ->where('status', 'completed')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get();

        return Inertia::render('Admin/Reports/Sales', [
            'from'         => $from->toDateString(),
            'to'           => $to->toDateString(),
            'orders'       => $orders->map(fn($o) => [
                'number'   => $o->order_number,
                'date'     => $o->created_at->locale('fr')->isoFormat('D MMM YYYY'),
                'client'   => $o->client->full_name,
                'items'    => $o->items->count(),
                'total'    => $o->total,
                'status'   => $o->status->label(),
            ]),
            'appointments' => $appointments->map(fn($a) => [
                'reference' => $a->reference,
                'date'      => $a->date->locale('fr')->isoFormat('D MMM YYYY'),
                'client'    => $a->client->full_name,
                'service'   => $a->service->name,
                'price'     => $a->price,
            ]),
            'summary' => [
                'orders_total'       => $orders->sum('total'),
                'appointments_total' => $appointments->sum('price'),
                'grand_total'        => $orders->sum('total') + $appointments->sum('price'),
            ],
        ]);
    }

    public function appointments(Request $request): Response
    {
        $year  = $request->year ?? now()->year;
        $month = $request->month;

        $query = Appointment::with(['client', 'service']);
        if ($month) {
            $query->whereYear('date', $year)->whereMonth('date', $month);
        } else {
            $query->whereYear('date', $year);
        }

        $appointments = $query->latest('date')->paginate(20)->withQueryString();

        return Inertia::render('Admin/Reports/Appointments', [
            'appointments'   => $appointments,
            'year'           => (int) $year,
            'month'          => $month,
            'availableYears' => $this->getAvailableYears(),
        ]);
    }

    public function clients(Request $request): Response
    {
        $clients = Client::withCount(['appointments', 'orders'])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Reports/Clients', [
            'clients' => $clients->through(fn($c) => [
                'full_name'          => $c->full_name,
                'email'              => $c->email,
                'is_vip'             => $c->is_vip,
                'hair_type'          => $c->hair_type,
                'appointments_count' => $c->appointments_count,
                'orders_count'       => $c->orders_count,
                'total_spent'        => $c->total_spent,
                'loyalty_points'     => $c->loyalty_points,
                'joined'             => $c->created_at->locale('fr')->isoFormat('D MMM YYYY'),
            ]),
        ]);
    }

    public function products(Request $request): Response
    {
        $year = $request->year ?? now()->year;

        $products = Product::with('category')
            ->withSum(['orderItems as revenue' => fn($q) =>
                $q->whereHas('order', fn($oq) => $oq->whereYear('created_at', $year)->whereNotIn('status', ['cancelled']))
            ], 'total')
            ->withSum(['orderItems as units_sold' => fn($q) =>
                $q->whereHas('order', fn($oq) => $oq->whereYear('created_at', $year)->whereNotIn('status', ['cancelled']))
            ], 'quantity')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn($p) => [
                'name'          => $p->name,
                'sku'           => $p->sku,
                'category'      => $p->category?->name,
                'price'         => $p->price,
                'stock'         => $p->stock,
                'units_sold'    => (int) ($p->units_sold ?? 0),
                'revenue'       => round($p->revenue ?? 0, 2),
                'thumbnail_url' => $p->thumbnail_url,
            ]);

        return Inertia::render('Admin/Reports/Products', [
            'products'       => $products,
            'year'           => (int) $year,
            'availableYears' => $this->getAvailableYears(),
        ]);
    }

    public function export(Request $request, string $type): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response
    {
        // Export CSV simple
        $filename = "export-{$type}-" . now()->format('Y-m-d') . ".csv";
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = match($type) {
            'clients'      => fn() => $this->exportClients(),
            'orders'       => fn() => $this->exportOrders(),
            'appointments' => fn() => $this->exportAppointments(),
            'payments'     => fn() => $this->exportPayments(),
            default        => fn() => '',
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    /* ── Helpers privés ──────────────────────────────────────────── */

    private function getAvailableYears(): array
    {
        $start = 2024;
        $end   = now()->year + 1;
        return range($end, $start);
    }

    private function exportClients(): void
    {
        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // BOM UTF-8
        fputcsv($out, ['Nom', 'Prénom', 'Email', 'Téléphone', 'Type de cheveux', 'VIP', 'Points fidélité', 'Total dépensé', 'Inscrit le'], ';');
        Client::chunk(200, function ($clients) use ($out) {
            foreach ($clients as $c) {
                fputcsv($out, [
                    $c->last_name, $c->first_name, $c->email, $c->phone,
                    $c->hair_type, $c->is_vip ? 'Oui' : 'Non',
                    $c->loyalty_points, number_format($c->total_spent, 2, ',', ' '),
                    $c->created_at->format('d/m/Y'),
                ], ';');
            }
        });
        fclose($out);
    }

    private function exportOrders(): void
    {
        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");
        fputcsv($out, ['N° commande', 'Client', 'Email', 'Statut', 'Total (€)', 'Date'], ';');
        Order::with('client')->chunk(200, function ($orders) use ($out) {
            foreach ($orders as $o) {
                fputcsv($out, [
                    $o->order_number, $o->client->full_name, $o->client->email,
                    $o->status->label(), number_format($o->total, 2, ',', ' '),
                    $o->created_at->format('d/m/Y'),
                ], ';');
            }
        });
        fclose($out);
    }

    private function exportAppointments(): void
    {
        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Référence', 'Client', 'Service', 'Date', 'Heure', 'Statut', 'Prix (€)'], ';');
        Appointment::with(['client', 'service'])->chunk(200, function ($appts) use ($out) {
            foreach ($appts as $a) {
                fputcsv($out, [
                    $a->reference, $a->client->full_name, $a->service->name,
                    $a->date->format('d/m/Y'), $a->start_time_formatted,
                    $a->status->label(), number_format($a->price, 2, ',', ' '),
                ], ';');
            }
        });
        fclose($out);
    }

    private function exportPayments(): void
    {
        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Référence', 'Client', 'Montant (€)', 'Méthode', 'Statut', 'Date'], ';');
        Payment::with('client')->chunk(200, function ($payments) use ($out) {
            foreach ($payments as $p) {
                fputcsv($out, [
                    $p->reference, $p->client->full_name,
                    number_format($p->amount, 2, ',', ' '),
                    $p->method->label(), $p->status,
                    $p->paid_at->format('d/m/Y'),
                ], ';');
            }
        });
        fclose($out);
    }
}