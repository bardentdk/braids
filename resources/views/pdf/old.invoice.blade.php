<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Facture {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1a1a2e;
            background: #fff;
            line-height: 1.5;
        }

        /* ── Layout ── */
        .page { padding: 40px 50px; min-height: 297mm; position: relative; }

        /* ── Header ── */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 30px;
            border-bottom: 2px solid #c4956a;
            margin-bottom: 35px;
        }
        .brand-name {
            font-size: 26px;
            font-weight: 700;
            color: #1a1a2e;
            letter-spacing: -0.5px;
        }
        .brand-subtitle {
            font-size: 10px;
            color: #c4956a;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-top: 2px;
        }
        .brand-info {
            font-size: 10px;
            color: #6b7280;
            margin-top: 8px;
            line-height: 1.6;
        }

        .invoice-badge {
            text-align: right;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: 700;
            color: #c4956a;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .invoice-number {
            font-size: 13px;
            color: #6b7280;
            margin-top: 4px;
        }

        /* ── Status badge ── */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 8px;
        }
        .status-paid    { background: #d1fae5; color: #065f46; }
        .status-draft   { background: #f3f4f6; color: #6b7280; }
        .status-sent    { background: #dbeafe; color: #1e40af; }
        .status-overdue { background: #fee2e2; color: #991b1b; }

        /* ── Addresses ── */
        .addresses {
            display: flex;
            justify-content: space-between;
            margin-bottom: 35px;
            gap: 20px;
        }
        .address-block { flex: 1; }
        .address-label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #c4956a;
            border-bottom: 1px solid #f5ede4;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .address-name { font-size: 13px; font-weight: 700; color: #1a1a2e; }
        .address-detail { font-size: 10px; color: #6b7280; line-height: 1.6; }

        /* ── Meta info ── */
        .meta-grid {
            display: flex;
            gap: 12px;
            margin-bottom: 35px;
        }
        .meta-item {
            flex: 1;
            background: #faf7f4;
            border: 1px solid #f5ede4;
            border-radius: 8px;
            padding: 12px 15px;
        }
        .meta-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #9ca3af;
            font-weight: 700;
        }
        .meta-value {
            font-size: 12px;
            font-weight: 700;
            color: #1a1a2e;
            margin-top: 3px;
        }

        /* ── Table ── */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        thead tr {
            background: #1a1a2e;
            color: #fff;
        }
        thead th {
            padding: 10px 14px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
        thead th:first-child { border-radius: 6px 0 0 6px; text-align: left; }
        thead th:last-child  { border-radius: 0 6px 6px 0; }

        tbody tr { border-bottom: 1px solid #f5ede4; }
        tbody tr:nth-child(even) { background: #faf7f4; }
        tbody td { padding: 11px 14px; font-size: 10px; vertical-align: top; }
        .td-desc { color: #1a1a2e; font-weight: 600; }
        .td-details { color: #9ca3af; font-size: 9px; margin-top: 2px; }
        .td-right { text-align: right; color: #1a1a2e; }

        /* ── Totaux ── */
        .totals-wrapper {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 35px;
        }
        .totals-table { width: 280px; }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 10px;
            border-bottom: 1px solid #f5ede4;
        }
        .totals-row:last-child { border-bottom: none; }
        .totals-label { color: #6b7280; }
        .totals-value { font-weight: 600; color: #1a1a2e; }
        .totals-total-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 16px;
            background: linear-gradient(135deg, #c4956a, #b07d52);
            border-radius: 8px;
            margin-top: 8px;
        }
        .totals-total-label { color: #fff; font-size: 11px; font-weight: 700; }
        .totals-total-value { color: #fff; font-size: 14px; font-weight: 700; }

        /* ── Paiements ── */
        .payments-section { margin-bottom: 30px; }
        .section-title {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #c4956a;
            border-bottom: 1px solid #f5ede4;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }
        .payment-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            background: #f0fdf4;
            border-radius: 6px;
            margin-bottom: 5px;
            border: 1px solid #d1fae5;
        }
        .payment-method { font-size: 10px; color: #065f46; font-weight: 600; }
        .payment-date   { font-size: 9px; color: #9ca3af; }
        .payment-amount { font-size: 11px; font-weight: 700; color: #065f46; }

        /* ── Amount due banner ── */
        .due-banner {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .due-label { font-size: 11px; font-weight: 700; color: #c2410c; }
        .due-amount { font-size: 20px; font-weight: 700; color: #c2410c; }

        /* ── Notes ── */
        .notes-section {
            background: #faf7f4;
            border-left: 3px solid #c4956a;
            border-radius: 0 8px 8px 0;
            padding: 12px 16px;
            margin-bottom: 20px;
        }
        .notes-text { font-size: 10px; color: #6b7280; line-height: 1.6; }

        /* ── Footer ── */
        .footer {
            position: absolute;
            bottom: 30px;
            left: 50px;
            right: 50px;
            border-top: 1px solid #f5ede4;
            padding-top: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .footer-brand { font-size: 9px; color: #9ca3af; }
        .footer-legal { font-size: 8px; color: #d1d5db; text-align: right; }

        /* ── Watermark PAYÉ ── */
        .watermark-paid {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 80px;
            font-weight: 900;
            color: rgba(16, 185, 129, 0.08);
            text-transform: uppercase;
            letter-spacing: 10px;
            pointer-events: none;
            z-index: 0;
        }
    </style>
</head>
<body>
<div class="page">

    {{-- Watermark PAYÉ --}}
    @if($invoice->status->value === 'paid')
        <div class="watermark-paid">PAYÉ</div>
    @endif

    {{-- ── Header ── --}}
    <div class="header">
        <div>
            <div class="brand-name">Patricia</div>
            <div class="brand-subtitle">Braids Studio</div>
            <div class="brand-info">
                {{ $settings['business_address'] ?? '12 rue des Tresses, 75001 Paris' }}<br>
                {{ $settings['business_phone'] ?? '+33 6 00 00 00 00' }}<br>
                {{ $settings['business_email'] ?? 'contact@patricia-braids.fr' }}<br>
                @if(!empty($settings['business_siret']))
                    SIRET : {{ $settings['business_siret'] }}
                @endif
            </div>
        </div>
        <div class="invoice-badge">
            <div class="invoice-title">Facture</div>
            <div class="invoice-number">{{ $invoice->invoice_number }}</div>
            <div>
                @php
                    $statusClasses = [
                        'paid'      => 'status-paid',
                        'draft'     => 'status-draft',
                        'sent'      => 'status-sent',
                        'overdue'   => 'status-overdue',
                        'cancelled' => 'status-draft',
                    ];
                    $statusLabels = [
                        'paid'      => 'Payée',
                        'draft'     => 'Brouillon',
                        'sent'      => 'Envoyée',
                        'overdue'   => 'En retard',
                        'cancelled' => 'Annulée',
                    ];
                @endphp
                <span class="status-badge {{ $statusClasses[$invoice->status->value] ?? 'status-draft' }}">
                    {{ $statusLabels[$invoice->status->value] ?? $invoice->status->value }}
                </span>
            </div>
        </div>
    </div>

    {{-- ── Adresses ── --}}
    <div class="addresses">
        <div class="address-block">
            <div class="address-label">Émetteur</div>
            <div class="address-name">Patricia Braids Studio</div>
            <div class="address-detail">
                {{ $settings['business_address'] ?? '12 rue des Tresses' }}<br>
                {{ $settings['business_city'] ?? '75001 Paris' }}<br>
                France
            </div>
        </div>
        <div class="address-block">
            <div class="address-label">Client</div>
            <div class="address-name">{{ $invoice->client_snapshot['name'] ?? $invoice->client->full_name }}</div>
            <div class="address-detail">
                {{ $invoice->client_snapshot['email'] ?? $invoice->client->email }}<br>
                @if(!empty($invoice->client_snapshot['phone']))
                    {{ $invoice->client_snapshot['phone'] }}<br>
                @endif
                @if(!empty($invoice->client_snapshot['address']))
                    {{ $invoice->client_snapshot['address'] }}<br>
                    {{ $invoice->client_snapshot['postal_code'] }} {{ $invoice->client_snapshot['city'] }}<br>
                    {{ $invoice->client_snapshot['country'] ?? 'France' }}
                @endif
            </div>
        </div>
    </div>

    {{-- ── Meta ── --}}
    <div class="meta-grid">
        <div class="meta-item">
            <div class="meta-label">Date d'émission</div>
            <div class="meta-value">{{ $invoice->issue_date->locale('fr')->isoFormat('D MMMM YYYY') }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Date d'échéance</div>
            <div class="meta-value">{{ $invoice->due_date->locale('fr')->isoFormat('D MMMM YYYY') }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Type</div>
            <div class="meta-value">
                @php
                    $types = ['order' => 'Commande', 'appointment' => 'Rendez-vous', 'manual' => 'Manuel'];
                @endphp
                {{ $types[$invoice->type] ?? ucfirst($invoice->type) }}
            </div>
        </div>
        @if($invoice->linked_order ?? $invoice->order)
        <div class="meta-item">
            <div class="meta-label">Référence</div>
            <div class="meta-value">{{ $invoice->order?->order_number ?? '—' }}</div>
        </div>
        @endif
    </div>

    {{-- ── Lignes ── --}}
    <table>
        <thead>
            <tr>
                <th style="text-align:left; width:45%">Description</th>
                <th style="text-align:right">Qté</th>
                <th style="text-align:right">Prix unit.</th>
                @if($invoice->items->sum('discount_percent') > 0)
                <th style="text-align:right">Remise</th>
                @endif
                <th style="text-align:right">Total HT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>
                    <div class="td-desc">{{ $item->description }}</div>
                    @if($item->details)
                        <div class="td-details">{{ $item->details }}</div>
                    @endif
                </td>
                <td class="td-right">{{ $item->quantity }}</td>
                <td class="td-right">{{ number_format($item->unit_price, 2, ',', ' ') }} €</td>
                @if($invoice->items->sum('discount_percent') > 0)
                <td class="td-right">
                    {{ $item->discount_percent > 0 ? $item->discount_percent . '%' : '—' }}
                </td>
                @endif
                <td class="td-right" style="font-weight:700">{{ number_format($item->total, 2, ',', ' ') }} €</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ── Totaux ── --}}
    <div class="totals-wrapper">
        <div class="totals-table">
            <div class="totals-row">
                <span class="totals-label">Sous-total HT</span>
                <span class="totals-value">{{ number_format($invoice->subtotal, 2, ',', ' ') }} €</span>
            </div>
            @if($invoice->discount_amount > 0)
            <div class="totals-row">
                <span class="totals-label">Remise</span>
                <span class="totals-value" style="color:#059669">- {{ number_format($invoice->discount_amount, 2, ',', ' ') }} €</span>
            </div>
            @endif
            <div class="totals-row">
                <span class="totals-label">TVA ({{ $invoice->tax_rate }}%)</span>
                <span class="totals-value">{{ number_format($invoice->tax_amount, 2, ',', ' ') }} €</span>
            </div>
            <div class="totals-total-row">
                <span class="totals-total-label">Total TTC</span>
                <span class="totals-total-value">{{ number_format($invoice->total, 2, ',', ' ') }} €</span>
            </div>
        </div>
    </div>

    {{-- ── Paiements reçus ── --}}
    @if($invoice->payments && $invoice->payments->count() > 0)
    <div class="payments-section">
        <div class="section-title">Paiements reçus</div>
        @foreach($invoice->payments as $payment)
        <div class="payment-row">
            <div>
                <div class="payment-method">{{ $payment->method->label() }}</div>
                @if($payment->notes)
                    <div class="payment-date">{{ $payment->notes }}</div>
                @endif
            </div>
            <div class="payment-date">{{ $payment->paid_at->locale('fr')->isoFormat('D MMM YYYY') }}</div>
            <div class="payment-amount">{{ number_format($payment->amount, 2, ',', ' ') }} €</div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ── Solde restant ── --}}
    @if($invoice->amount_due > 0 && $invoice->status->value !== 'cancelled')
    <div class="due-banner">
        <div class="due-label">Solde restant dû</div>
        <div class="due-amount">{{ number_format($invoice->amount_due, 2, ',', ' ') }} €</div>
    </div>
    @endif

    {{-- ── Notes ── --}}
    @if($invoice->notes)
    <div class="notes-section">
        <div style="font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:1.5px; color:#c4956a; margin-bottom:5px;">Notes</div>
        <div class="notes-text">{{ $invoice->notes }}</div>
    </div>
    @endif

    {{-- ── Conditions ── --}}
    @if($invoice->terms || !empty($settings['invoice_terms']))
    <div class="notes-section" style="background:#f9fafb; border-left-color:#9ca3af">
        <div style="font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:1.5px; color:#9ca3af; margin-bottom:5px;">Conditions</div>
        <div class="notes-text">{{ $invoice->terms ?? $settings['invoice_terms'] }}</div>
    </div>
    @endif

    {{-- ── Footer ── --}}
    <div class="footer">
        <div class="footer-brand">
            Patricia Braids Studio — {{ $invoice->invoice_number }}
        </div>
        <div class="footer-legal">
            @if(!empty($settings['business_siret']))
                SIRET {{ $settings['business_siret'] }} —
            @endif
            Document généré le {{ now()->locale('fr')->isoFormat('D MMM YYYY') }}
        </div>
    </div>

</div>
</body>
</html>