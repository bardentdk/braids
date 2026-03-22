<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InvoiceRequest;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Services\BrevoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function __construct(protected BrevoService $brevo) {}

    public function index(Request $request): Response
    {
        $query = Invoice::with('client')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->search, fn($q) => $q
                ->where('invoice_number', 'like', "%{$request->search}%")
                ->orWhereHas('client', fn($cq) => $cq->search($request->search))
            )
            ->when($request->overdue, fn($q) => $q
                ->where('status', '!=', 'paid')
                ->where('status', '!=', 'cancelled')
                ->where('due_date', '<', today())
            )
            ->latest('issue_date');

        $invoices = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Invoices/Index', [
            'invoices' => $invoices->through(fn($i) => [
                'id'             => $i->id,
                'invoice_number' => $i->invoice_number,
                'status'         => $i->status->value,
                'status_label'   => $i->status->label(),
                'status_color'   => $i->status->color(),
                'type'           => $i->type,
                'total'          => $i->total,
                'amount_due'     => $i->amount_due,
                'issue_date'     => $i->issue_date->locale('fr')->isoFormat('D MMM YYYY'),
                'due_date'       => $i->due_date->locale('fr')->isoFormat('D MMM YYYY'),
                'is_overdue'     => $i->is_overdue,
                'paid_at'        => $i->paid_at?->locale('fr')->isoFormat('D MMM YYYY'),
                'client'         => ['id' => $i->client->id, 'full_name' => $i->client->full_name],
            ]),
            'filters'  => $request->only(['status', 'type', 'search', 'overdue']),
            'statuses' => collect(InvoiceStatus::cases())->map(fn($s) => [
                'value' => $s->value, 'label' => $s->label(),
            ]),
            'stats' => [
                'total_due'     => Invoice::where('status', '!=', 'paid')->where('status', '!=', 'cancelled')->sum('amount_due'),
                'overdue_count' => Invoice::where('status', '!=', 'paid')->where('due_date', '<', today())->count(),
                'paid_month'    => Invoice::where('status', 'paid')->whereMonth('paid_at', now()->month)->sum('total'),
                'draft_count'   => Invoice::where('status', 'draft')->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Invoices/Create', [
            'clients'         => Client::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email']),
            'payment_methods' => collect(PaymentMethod::cases())->map(fn($m) => [
                'value' => $m->value, 'label' => $m->label(),
            ]),
        ]);
    }

    public function store(InvoiceRequest $request): RedirectResponse
    {
        $data   = $request->validated();
        $client = Client::findOrFail($data['client_id']);

        $invoice = Invoice::create([
            ...$data,
            'status'          => InvoiceStatus::Draft,
            'type'            => 'manual',
            'client_snapshot' => [
                'name'        => $client->full_name,
                'email'       => $client->email,
                'phone'       => $client->phone,
                'address'     => $client->address,
                'city'        => $client->city,
                'postal_code' => $client->postal_code,
                'country'     => $client->country,
            ],
        ]);

        // Lignes de facture
        foreach ($data['items'] as $i => $item) {
            $qty      = $item['quantity'] ?? 1;
            $price    = $item['unit_price'];
            $discount = $item['discount_percent'] ?? 0;
            $total    = $price * $qty * (1 - $discount / 100);

            InvoiceItem::create([
                'invoice_id'      => $invoice->id,
                'description'     => $item['description'],
                'details'         => $item['details'] ?? null,
                'unit_price'      => $price,
                'quantity'        => $qty,
                'discount_percent'=> $discount,
                'total'           => round($total, 2),
                'sort_order'      => $i,
            ]);
        }

        // Recalculer les totaux
        $this->recalculateTotals($invoice);

        return redirect()->route('admin.factures.show', $invoice)
                         ->with('success', "Facture {$invoice->invoice_number} créée.");
    }

    public function show(Invoice $facture): Response
    {
        $facture->load(['client', 'items', 'payments', 'order', 'appointment.service']);

        return Inertia::render('Admin/Invoices/Show', [
            'invoice' => [
                'id'              => $facture->id,
                'invoice_number'  => $facture->invoice_number,
                'status'          => $facture->status->value,
                'status_label'    => $facture->status->label(),
                'status_color'    => $facture->status->color(),
                'type'            => $facture->type,
                'client_snapshot' => $facture->client_snapshot,
                'subtotal'        => $facture->subtotal,
                'discount_amount' => $facture->discount_amount,
                'tax_rate'        => $facture->tax_rate,
                'tax_amount'      => $facture->tax_amount,
                'total'           => $facture->total,
                'amount_paid'     => $facture->amount_paid,
                'amount_due'      => $facture->amount_due,
                'issue_date'      => $facture->issue_date->locale('fr')->isoFormat('D MMMM YYYY'),
                'due_date'        => $facture->due_date->locale('fr')->isoFormat('D MMMM YYYY'),
                'due_date_formatted' => $facture->due_date_formatted,
                'is_overdue'      => $facture->is_overdue,
                'paid_at'         => $facture->paid_at?->locale('fr')->isoFormat('D MMMM YYYY'),
                'sent_at'         => $facture->sent_at?->locale('fr')->isoFormat('D MMMM YYYY'),
                'notes'           => $facture->notes,
                'terms'           => $facture->terms,
                'client'          => [
                    'id'        => $facture->client->id,
                    'full_name' => $facture->client->full_name,
                    'email'     => $facture->client->email,
                ],
                'items' => $facture->items->map(fn($item) => [
                    'id'              => $item->id,
                    'description'     => $item->description,
                    'details'         => $item->details,
                    'unit_price'      => $item->unit_price,
                    'quantity'        => $item->quantity,
                    'discount_percent'=> $item->discount_percent,
                    'total'           => $item->total,
                ]),
                'payments' => $facture->payments->map(fn($p) => [
                    'id'      => $p->id,
                    'amount'  => $p->amount,
                    'method'  => $p->method->label(),
                    'paid_at' => $p->paid_at->locale('fr')->isoFormat('D MMM YYYY'),
                    'notes'   => $p->notes,
                ]),
                'linked_order'       => $facture->order ? ['id' => $facture->order->id, 'number' => $facture->order->order_number] : null,
                'linked_appointment' => $facture->appointment ? ['id' => $facture->appointment->id, 'ref' => $facture->appointment->reference] : null,
            ],
            'payment_methods' => collect(PaymentMethod::cases())->map(fn($m) => [
                'value' => $m->value, 'label' => $m->label(),
            ]),
        ]);
    }

    public function edit(Invoice $facture): Response
    {
        return Inertia::render('Admin/Invoices/Edit', [
            'invoice' => $facture->load(['items', 'client']),
            'clients' => Client::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email']),
        ]);
    }

    public function update(InvoiceRequest $request, Invoice $facture): RedirectResponse
    {
        $data = $request->validated();
        $facture->update($data);

        // Re-créer les lignes
        $facture->items()->delete();
        foreach ($data['items'] as $i => $item) {
            $qty      = $item['quantity'] ?? 1;
            $price    = $item['unit_price'];
            $discount = $item['discount_percent'] ?? 0;
            $total    = $price * $qty * (1 - $discount / 100);

            InvoiceItem::create([
                'invoice_id'      => $facture->id,
                'description'     => $item['description'],
                'unit_price'      => $price,
                'quantity'        => $qty,
                'discount_percent'=> $discount,
                'total'           => round($total, 2),
                'sort_order'      => $i,
            ]);
        }

        $this->recalculateTotals($facture);

        return redirect()->route('admin.factures.show', $facture)
                         ->with('success', 'Facture mise à jour.');
    }

    public function destroy(Invoice $facture): RedirectResponse
    {
        if ($facture->status->value === 'paid') {
            return back()->with('error', 'Impossible de supprimer une facture payée.');
        }

        if ($facture->pdf_path) Storage::disk('public')->delete($facture->pdf_path);
        $num = $facture->invoice_number;
        $facture->delete();

        return redirect()->route('admin.factures.index')
                         ->with('success', "Facture {$num} supprimée.");
    }

    public function pdf(Invoice $facture): HttpResponse
    {
        $facture->load(['client', 'items', 'payments']);

        $settings = \App\Models\Setting::getGroup('general');
        $invoiceSettings = \App\Models\Setting::getGroup('invoice');

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice'  => $facture,
            'settings' => array_merge($settings, $invoiceSettings),
        ])->setPaper('A4', 'portrait');

        $filename = "facture-{$facture->invoice_number}.pdf";

        // Sauvegarder pour envoi par email
        $pdfContent = $pdf->output();
        $path = "invoices/{$filename}";
        Storage::disk('public')->put($path, $pdfContent);
        $facture->update(['pdf_path' => $path]);

        return $pdf->stream($filename);
    }

    public function send(Invoice $facture): RedirectResponse
    {
        $facture->load(['client', 'items', 'payments']);
        $client   = $facture->client;
        $settings = array_merge(
            \App\Models\Setting::getGroup('general'),
            \App\Models\Setting::getGroup('invoice')
        );

        // Générer le PDF
        $pdf         = Pdf::loadView('pdf.invoice', ['invoice' => $facture, 'settings' => $settings])->setPaper('A4');
        $pdfContent  = $pdf->output();
        $filename    = "facture-{$facture->invoice_number}.pdf";

        // Corps email
        $html = view('emails.invoice', ['invoice' => $facture, 'client' => $client])->render();

        $sent = $this->brevo->send(
            toEmail:     $client->email,
            toName:      $client->full_name,
            subject:     "Votre facture {$facture->invoice_number} — Patricia Braids",
            htmlContent: $html,
            attachments: [['content' => $pdfContent, 'name' => $filename]],
        );

        if ($sent) {
            $facture->update([
                'status'  => InvoiceStatus::Sent,
                'sent_at' => now(),
            ]);
            return back()->with('success', "Facture envoyée à {$client->email}.");
        }

        return back()->with('error', 'Erreur lors de l\'envoi de la facture.');
    }

    public function markPaid(Request $request, Invoice $facture): RedirectResponse
    {
        $request->validate([
            'method'   => 'required|in:' . implode(',', array_column(PaymentMethod::cases(), 'value')),
            'paid_at'  => 'nullable|date',
            'notes'    => 'nullable|string|max:255',
        ]);

        $paidAt = $request->paid_at ? \Carbon\Carbon::parse($request->paid_at) : now();

        Payment::create([
            'invoice_id' => $facture->id,
            'client_id'  => $facture->client_id,
            'amount'     => $facture->amount_due,
            'method'     => $request->method,
            'status'     => 'completed',
            'paid_at'    => $paidAt,
            'notes'      => $request->notes,
        ]);

        $facture->update([
            'status'      => InvoiceStatus::Paid,
            'amount_paid' => $facture->total,
            'amount_due'  => 0,
            'paid_at'     => $paidAt,
        ]);

        return back()->with('success', 'Facture marquée comme payée.');
    }

    public function addPayment(Request $request, Invoice $facture): RedirectResponse
    {
        $request->validate([
            'amount'  => "required|numeric|min:0.01|max:{$facture->amount_due}",
            'method'  => 'required|in:' . implode(',', array_column(PaymentMethod::cases(), 'value')),
            'paid_at' => 'nullable|date',
            'notes'   => 'nullable|string',
        ]);

        $paidAt = $request->paid_at ? \Carbon\Carbon::parse($request->paid_at) : now();

        Payment::create([
            'invoice_id' => $facture->id,
            'client_id'  => $facture->client_id,
            'amount'     => $request->amount,
            'method'     => $request->method,
            'status'     => 'completed',
            'paid_at'    => $paidAt,
            'notes'      => $request->notes,
        ]);

        $newPaid = $facture->amount_paid + $request->amount;
        $newDue  = max(0, $facture->total - $newPaid);

        $facture->update([
            'amount_paid' => $newPaid,
            'amount_due'  => $newDue,
            'status'      => $newDue <= 0 ? InvoiceStatus::Paid : $facture->status,
            'paid_at'     => $newDue <= 0 ? $paidAt : null,
        ]);

        return back()->with('success', 'Paiement enregistré.');
    }

    /* ── Helpers ─────────────────────────────────────────────────── */

    private function recalculateTotals(Invoice $invoice): void
    {
        $invoice->refresh()->load('items');
        $subtotal = $invoice->items->sum('total');
        $taxRate  = $invoice->tax_rate ?? 20;
        $tax      = round($subtotal * ($taxRate / 100), 2);
        $total    = round($subtotal + $tax - ($invoice->discount_amount ?? 0), 2);

        $invoice->update([
            'subtotal'   => $subtotal,
            'tax_amount' => $tax,
            'total'      => $total,
            'amount_due' => $total - $invoice->amount_paid,
        ]);
    }
}