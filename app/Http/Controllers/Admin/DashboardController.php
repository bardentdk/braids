<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Review;
use App\Enums\AppointmentStatus;
use App\Enums\OrderStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $now       = Carbon::now();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        // ── KPIs principaux ───────────────────────────────────────
        $kpis = [
            'revenue' => [
                'current'  => $this->getMonthRevenue($now),
                'previous' => $this->getMonthRevenue($now->copy()->subMonth()),
                'label'    => 'Chiffre d\'affaires',
                'suffix'   => '€',
                'icon'     => 'PhCurrencyEur',
            ],
            'appointments' => [
                'current'  => Appointment::whereMonth('date', $now->month)
                                         ->whereYear('date', $now->year)
                                         ->count(),
                'previous' => Appointment::whereMonth('date', $now->copy()->subMonth()->month)
                                         ->whereYear('date', $now->copy()->subMonth()->year)
                                         ->count(),
                'label'    => 'Rendez-vous',
                'suffix'   => '',
                'icon'     => 'PhCalendarCheck',
            ],
            'new_clients' => [
                'current'  => Client::whereMonth('created_at', $now->month)
                                    ->whereYear('created_at', $now->year)
                                    ->count(),
                'previous' => Client::whereMonth('created_at', $now->copy()->subMonth()->month)
                                    ->whereYear('created_at', $now->copy()->subMonth()->year)
                                    ->count(),
                'label'    => 'Nouveaux clients',
                'suffix'   => '',
                'icon'     => 'PhUserPlus',
            ],
            'orders' => [
                'current'  => Order::whereMonth('created_at', $now->month)
                                   ->whereYear('created_at', $now->year)
                                   ->whereNotIn('status', ['cancelled'])
                                   ->count(),
                'previous' => Order::whereMonth('created_at', $now->copy()->subMonth()->month)
                                   ->whereYear('created_at', $now->copy()->subMonth()->year)
                                   ->whereNotIn('status', ['cancelled'])
                                   ->count(),
                'label'    => 'Commandes',
                'suffix'   => '',
                'icon'     => 'PhShoppingBag',
            ],
        ];

        // Calcul des évolutions
        foreach ($kpis as &$kpi) {
            $prev = $kpi['previous'];
            $curr = $kpi['current'];
            $kpi['evolution'] = $prev > 0
                ? round((($curr - $prev) / $prev) * 100, 1)
                : ($curr > 0 ? 100 : 0);
        }
        unset($kpi);

        // ── Agenda du jour ────────────────────────────────────────
        $todayAppointments = Appointment::with(['client', 'service'])
            ->whereDate('date', today())
            ->orderBy('start_time')
            ->get()
            ->map(fn($a) => [
                'id'         => $a->id,
                'reference'  => $a->reference,
                'start_time' => $a->start_time_formatted,
                'end_time'   => $a->end_time_formatted,
                'status'     => $a->status->value,
                'status_label'=> $a->status->label(),
                'status_color'=> $a->status->color(),
                'client'     => [
                    'name'   => $a->client->full_name,
                    'avatar' => $a->client->avatar_url,
                    'phone'  => $a->client->phone,
                ],
                'service'    => [
                    'name'     => $a->service->name,
                    'duration' => $a->service->duration_formatted,
                    'price'    => $a->price,
                ],
            ]);

        // ── Prochains RDV (7 jours) ───────────────────────────────
        $upcomingAppointments = Appointment::with(['client', 'service'])
            ->upcoming()
            ->whereDate('date', '>', today())
            ->whereDate('date', '<=', now()->addDays(7))
            ->take(8)
            ->get()
            ->map(fn($a) => [
                'id'         => $a->id,
                'reference'  => $a->reference,
                'date'       => $a->date->locale('fr')->isoFormat('ddd D MMM'),
                'start_time' => $a->start_time_formatted,
                'status'     => $a->status->value,
                'status_color'=> $a->status->color(),
                'client'     => ['name' => $a->client->full_name, 'avatar' => $a->client->avatar_url],
                'service'    => ['name' => $a->service->name],
            ]);

        // ── Dernières commandes ───────────────────────────────────
        $recentOrders = Order::with('client')
            ->latest()
            ->take(6)
            ->get()
            ->map(fn($o) => [
                'id'           => $o->id,
                'order_number' => $o->order_number,
                'total'        => $o->total,
                'status'       => $o->status->value,
                'status_label' => $o->status->label(),
                'status_color' => $o->status->color(),
                'date'         => $o->created_at->locale('fr')->isoFormat('D MMM'),
                'client'       => ['name' => $o->client->full_name],
            ]);

        // ── Factures en retard ────────────────────────────────────
        $overdueInvoices = Invoice::with('client')
            ->where('status', '!=', 'paid')
            ->where('status', '!=', 'cancelled')
            ->where('due_date', '<', today())
            ->orderBy('due_date')
            ->take(5)
            ->get()
            ->map(fn($i) => [
                'id'             => $i->id,
                'invoice_number' => $i->invoice_number,
                'total'          => $i->total,
                'amount_due'     => $i->amount_due,
                'due_date'       => $i->due_date->locale('fr')->isoFormat('D MMM YYYY'),
                'days_overdue'   => $i->due_date->diffInDays(today()),
                'client'         => ['name' => $i->client->full_name],
            ]);

        // ── Stocks faibles ────────────────────────────────────────
        $lowStockProducts = Product::lowStock()
            ->where('is_active', true)
            ->with('category')
            ->take(5)
            ->get()
            ->map(fn($p) => [
                'id'        => $p->id,
                'name'      => $p->name,
                'stock'     => $p->stock,
                'threshold' => $p->low_stock_alert,
                'thumbnail' => $p->thumbnail_url,
                'category'  => $p->category?->name,
            ]);

        // ── Revenue chart (12 derniers mois) ─────────────────────
        $revenueChart = collect(range(11, 0))->map(function ($i) {
            $month    = Carbon::now()->subMonths($i);
            $revenue  = Payment::whereMonth('paid_at', $month->month)
                               ->whereYear('paid_at', $month->year)
                               ->where('status', 'completed')
                               ->sum('amount');
            return [
                'month'   => $month->locale('fr')->isoFormat('MMM YY'),
                'revenue' => round($revenue, 2),
            ];
        })->values();

        // ── Répartition CA par source ─────────────────────────────
        $revenueBySource = [
            'appointments' => Payment::whereHas('invoice', fn($q) => $q->where('type', 'appointment'))
                                     ->where('status', 'completed')
                                     ->whereMonth('paid_at', $now->month)
                                     ->sum('amount'),
            'orders'       => Payment::whereHas('invoice', fn($q) => $q->where('type', 'order'))
                                     ->where('status', 'completed')
                                     ->whereMonth('paid_at', $now->month)
                                     ->sum('amount'),
            'manual'       => Payment::whereHas('invoice', fn($q) => $q->where('type', 'manual'))
                                     ->where('status', 'completed')
                                     ->whereMonth('paid_at', $now->month)
                                     ->sum('amount'),
        ];

        // ── Avis en attente ───────────────────────────────────────
        $pendingReviews = Review::with(['client', 'service', 'product'])
            ->where('is_approved', false)
            ->latest()
            ->take(3)
            ->get()
            ->map(fn($r) => [
                'id'      => $r->id,
                'rating'  => $r->rating,
                'comment' => \Str::limit($r->comment, 80),
                'client'  => ['name' => $r->client->full_name],
                'subject' => $r->service?->name ?? $r->product?->name ?? 'Général',
            ]);

        // ── Stats globales ────────────────────────────────────────
        $stats = [
            'total_clients'      => Client::count(),
            'total_revenue'      => Payment::where('status', 'completed')->sum('amount'),
            'total_appointments' => Appointment::count(),
            'avg_rating'         => Review::where('is_approved', true)->avg('rating'),
            'pending_appointments'=> Appointment::where('status', 'pending')->count(),
            'pending_orders'     => Order::where('status', 'pending')->count(),
            'unread_reviews'     => Review::where('is_approved', false)->count(),
            'total_products'     => Product::where('is_active', true)->count(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'kpis'               => $kpis,
            'stats'              => $stats,
            'todayAppointments'  => $todayAppointments,
            'upcomingAppointments'=> $upcomingAppointments,
            'recentOrders'       => $recentOrders,
            'overdueInvoices'    => $overdueInvoices,
            'lowStockProducts'   => $lowStockProducts,
            'revenueChart'       => $revenueChart,
            'revenueBySource'    => $revenueBySource,
            'pendingReviews'     => $pendingReviews,
            'currentMonth'       => $now->locale('fr')->isoFormat('MMMM YYYY'),
        ]);
    }

    private function getMonthRevenue(Carbon $date): float
    {
        return Payment::where('status', 'completed')
                      ->whereMonth('paid_at', $date->month)
                      ->whereYear('paid_at', $date->year)
                      ->sum('amount');
    }
}