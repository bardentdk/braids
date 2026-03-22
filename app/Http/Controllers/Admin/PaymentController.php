<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Enums\PaymentMethod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $payments = Payment::with(['client', 'invoice'])
            ->when($request->method, fn($q) => $q->where('method', $request->method))
            ->when($request->date_from, fn($q) => $q->whereDate('paid_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('paid_at', '<=', $request->date_to))
            ->when($request->search, fn($q) => $q->whereHas('client', fn($cq) => $cq->search($request->search))
                ->orWhere('reference', 'like', "%{$request->search}%")
            )
            ->latest('paid_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments->through(fn($p) => [
                'id'             => $p->id,
                'reference'      => $p->reference,
                'amount'         => $p->amount,
                'method'         => $p->method->value,
                'method_label'   => $p->method->label(),
                'status'         => $p->status,
                'paid_at'        => $p->paid_at->locale('fr')->isoFormat('D MMM YYYY à HH:mm'),
                'notes'          => $p->notes,
                'client'         => ['id' => $p->client->id, 'full_name' => $p->client->full_name],
                'invoice'        => ['id' => $p->invoice->id, 'invoice_number' => $p->invoice->invoice_number],
            ]),
            'filters' => $request->only(['method', 'date_from', 'date_to', 'search']),
            'methods' => collect(PaymentMethod::cases())->map(fn($m) => [
                'value' => $m->value, 'label' => $m->label(),
            ]),
            'stats' => [
                'total'     => Payment::where('status', 'completed')->sum('amount'),
                'this_month'=> Payment::where('status', 'completed')->whereMonth('paid_at', now()->month)->sum('amount'),
                'count'     => Payment::where('status', 'completed')->count(),
            ],
        ]);
    }

    public function show(Payment $payment): Response
    {
        $payment->load(['client', 'invoice']);

        return Inertia::render('Admin/Payments/Show', [
            'payment' => [
                'id'           => $payment->id,
                'reference'    => $payment->reference,
                'amount'       => $payment->amount,
                'method_label' => $payment->method->label(),
                'status'       => $payment->status,
                'paid_at'      => $payment->paid_at->locale('fr')->isoFormat('D MMMM YYYY à HH:mm'),
                'notes'        => $payment->notes,
                'client'       => $payment->client,
                'invoice'      => [
                    'id'             => $payment->invoice->id,
                    'invoice_number' => $payment->invoice->invoice_number,
                    'total'          => $payment->invoice->total,
                    'status'         => $payment->invoice->status->value,
                ],
            ],
        ]);
    }
}