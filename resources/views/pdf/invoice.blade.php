<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<title>Facture {{ $invoice->invoice_number }}</title>
<style>
    /* ═══════════════════════════════════════════════════════════
    RESET & BASE
    ════════════════════════════════════════════════════════════ */
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'DejaVu Sans', 'Helvetica', sans-serif;
        font-size: 9.5pt;
        color: #1a1a2e;
        background: #ffffff;
        line-height: 1.5;
    }

    /* ═══════════════════════════════════════════════════════════
    STRUCTURE PAGE
    ════════════════════════════════════════════════════════════ */
    .page {
        position: relative;
        width: 210mm;
        min-height: 297mm;
        overflow: hidden;
    }

    /* ─── Bande latérale gauche ─── */
    .side-bar {
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 6px;
        background: #c4956a;
        z-index: 10;
    }

    /* ─── Pattern diagonal en arrière-plan ─── */
    .bg-pattern {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        z-index: 0;
        opacity: 1;
    }

    /* ─── Cercle décoratif haut-droit ─── */
    .deco-circle-tr {
        position: absolute;
        top: -60px;
        right: -60px;
        width: 220px;
        height: 220px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(196,149,106,0.08) 0%, rgba(196,149,106,0) 70%);
        z-index: 0;
    }

    /* ─── Cercle décoratif bas-gauche ─── */
    .deco-circle-bl {
        position: absolute;
        bottom: 60px;
        left: -40px;
        width: 160px;
        height: 160px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(212,175,55,0.07) 0%, rgba(212,175,55,0) 70%);
        z-index: 0;
    }

    .content {
        position: relative;
        z-index: 5;
        padding: 38px 46px 120px 52px;
    }

    /* ═══════════════════════════════════════════════════════════
    HEADER
    ════════════════════════════════════════════════════════════ */
    .header-table {
        width: 100%;
        margin-bottom: 32px;
    }

    .brand-mark {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #c4956a;
        margin-right: 6px;
        vertical-align: middle;
    }

    .brand-name {
        font-size: 22pt;
        font-weight: 700;
        color: #0d0d1a;
        letter-spacing: -0.5px;
        line-height: 1;
    }

    .brand-tagline {
        font-size: 7pt;
        letter-spacing: 4px;
        text-transform: uppercase;
        color: #c4956a;
        margin-top: 3px;
        font-weight: 400;
    }

    .brand-info {
        font-size: 7.5pt;
        color: #9ca3af;
        margin-top: 10px;
        line-height: 1.8;
    }

    /* Badge FACTURE à droite */
    .invoice-label-wrap { text-align: right; }

    .invoice-word {
        font-size: 34pt;
        font-weight: 700;
        letter-spacing: 6px;
        text-transform: uppercase;
        color: #0d0d1a;
        opacity: 0.06;
        line-height: 1;
        display: block;
    }

    .invoice-number-big {
        font-size: 14pt;
        font-weight: 700;
        color: #c4956a;
        letter-spacing: 1px;
        display: block;
        margin-top: -8px;
    }

    .invoice-date-label {
        font-size: 7pt;
        color: #9ca3af;
        letter-spacing: 2px;
        text-transform: uppercase;
        margin-top: 4px;
        display: block;
    }

    /* ─── Ligne décorative header ─── */
    .header-divider {
        width: 100%;
        height: 1px;
        margin-bottom: 28px;
        position: relative;
    }

    .header-divider-line {
        width: 100%;
        height: 1px;
        background: #e5e7eb;
    }

    .header-divider-accent {
        position: absolute;
        left: 0; top: 0;
        width: 80px;
        height: 2px;
        background: #c4956a;
        margin-top: -0.5px;
    }

    /* ═══════════════════════════════════════════════════════════
    STATUS BADGE
    ════════════════════════════════════════════════════════════ */
    .status-bar {
        margin-bottom: 28px;
    }

    .status-pill {
        display: inline-block;
        padding: 4px 14px;
        border-radius: 30px;
        font-size: 7.5pt;
        font-weight: 700;
        letter-spacing: 2px;
        text-transform: uppercase;
    }

    .status-paid     { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    .status-draft    { background: #f9fafb; color: #6b7280; border: 1px solid #d1d5db; }
    .status-sent     { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
    .status-overdue  { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    .status-cancelled{ background: #f9fafb; color: #9ca3af; border: 1px solid #e5e7eb; }

    .due-warning {
        display: inline-block;
        margin-left: 10px;
        padding: 4px 12px;
        border-radius: 30px;
        font-size: 7.5pt;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        background: #fff7ed;
        color: #c2410c;
        border: 1px solid #fed7aa;
    }

    /* ═══════════════════════════════════════════════════════════
    PARTIES (émetteur / client)
    ════════════════════════════════════════════════════════════ */
    .parties-table { width: 100%; margin-bottom: 28px; }
    .parties-table td { vertical-align: top; width: 50%; }

    .party-box {
        padding: 16px 18px;
        border-radius: 8px;
    }

    .party-box-left {
        background: #0d0d1a;
        margin-right: 8px;
    }

    .party-box-right {
        background: #faf7f4;
        border: 1px solid #ede8e3;
        margin-left: 8px;
    }

    .party-label {
        font-size: 6.5pt;
        letter-spacing: 3px;
        text-transform: uppercase;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .party-label-light { color: rgba(196,149,106,0.9); }
    .party-label-dark  { color: #c4956a; }

    .party-name {
        font-size: 11pt;
        font-weight: 700;
        margin-bottom: 6px;
    }

    .party-name-light { color: #ffffff; }
    .party-name-dark  { color: #0d0d1a; }

    .party-detail { font-size: 7.5pt; line-height: 1.8; }
    .party-detail-light { color: rgba(255,255,255,0.5); }
    .party-detail-dark  { color: #6b7280; }

    /* ═══════════════════════════════════════════════════════════
    META DATES
    ════════════════════════════════════════════════════════════ */
    .meta-table { width: 100%; margin-bottom: 28px; }
    .meta-table td { width: 25%; vertical-align: top; }

    .meta-cell {
        padding: 10px 14px;
        border-left: 2px solid #f3f4f6;
        margin-right: 4px;
    }

    .meta-cell-accent {
        border-left-color: #c4956a;
    }

    .meta-cell-label {
        font-size: 6.5pt;
        letter-spacing: 2.5px;
        text-transform: uppercase;
        color: #9ca3af;
        font-weight: 700;
        display: block;
        margin-bottom: 3px;
    }

    .meta-cell-value {
        font-size: 9pt;
        font-weight: 700;
        color: #0d0d1a;
        display: block;
    }

    /* ═══════════════════════════════════════════════════════════
    TABLE LIGNES
    ════════════════════════════════════════════════════════════ */
    .section-title {
        font-size: 6.5pt;
        letter-spacing: 3px;
        text-transform: uppercase;
        color: #9ca3af;
        font-weight: 700;
        margin-bottom: 10px;
        display: block;
    }

    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 6px;
    }

    /* Thead */
    .items-table thead tr {
        background: #0d0d1a;
    }

    .items-table thead th {
        padding: 9px 12px;
        font-size: 6.5pt;
        font-weight: 700;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: rgba(255,255,255,0.6);
    }

    .items-table thead th:first-child { border-radius: 6px 0 0 6px; text-align: left; }
    .items-table thead th:last-child  { border-radius: 0 6px 6px 0; }
    .items-table thead th.right { text-align: right; }
    .items-table thead th.center { text-align: center; }

    /* Accent dans le th */
    .th-accent {
        color: #c4956a !important;
    }

    /* Tbody */
    .items-table tbody tr:nth-child(odd) {
        background: #faf7f4;
    }

    .items-table tbody tr:nth-child(even) {
        background: #ffffff;
    }

    .items-table tbody td {
        padding: 10px 12px;
        font-size: 8.5pt;
        border-bottom: 1px solid #f3f0ed;
        vertical-align: top;
    }

    .td-desc       { font-weight: 600; color: #0d0d1a; }
    .td-desc-sub   { font-size: 7pt; color: #9ca3af; margin-top: 2px; font-weight: 400; }
    .td-right      { text-align: right; color: #374151; }
    .td-center     { text-align: center; color: #374151; }
    .td-total      { text-align: right; font-weight: 700; color: #0d0d1a; }
    .td-discount   { text-align: center; color: #059669; font-size: 8pt; font-weight: 600; }

    /* ═══════════════════════════════════════════════════════════
    TOTAUX
    ════════════════════════════════════════════════════════════ */
    .totals-wrap {
        width: 100%;
        margin-bottom: 24px;
    }

    .totals-spacer { width: 58%; }

    .totals-table-inner {
        width: 42%;
        float: right;
    }

    .totals-row-table {
        width: 100%;
        border-collapse: collapse;
    }

    .totals-row-table tr td {
        padding: 6px 0;
        font-size: 8.5pt;
        border-bottom: 1px solid #f3f4f6;
    }

    .totals-row-table tr:last-of-type td { border-bottom: none; }

    .totals-label { color: #6b7280; }
    .totals-value { text-align: right; font-weight: 600; color: #0d0d1a; }

    .totals-discount-label { color: #059669; }
    .totals-discount-value { text-align: right; font-weight: 600; color: #059669; }

    /* Grand total box */
    .grand-total-box {
        margin-top: 8px;
        padding: 12px 16px;
        border-radius: 8px;
        background: #0d0d1a;
        width: 100%;
    }

    .grand-total-label {
        font-size: 8pt;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: rgba(255,255,255,0.5);
        font-weight: 700;
    }

    .grand-total-value {
        font-size: 17pt;
        font-weight: 700;
        color: #c4956a;
        float: right;
        margin-top: -4px;
    }

    /* ═══════════════════════════════════════════════════════════
    PAIEMENTS
    ════════════════════════════════════════════════════════════ */
    .payments-section { margin-bottom: 20px; }

    .payment-row {
        padding: 9px 14px;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        border-radius: 6px;
        margin-bottom: 5px;
        width: 100%;
    }

    .payment-row-table { width: 100%; }
    .payment-method { font-size: 8.5pt; font-weight: 700; color: #065f46; }
    .payment-date   { font-size: 7.5pt; color: #6b7280; text-align: center; }
    .payment-amount { font-size: 10pt; font-weight: 700; color: #065f46; text-align: right; }
    .payment-note   { font-size: 7pt; color: #9ca3af; }

    /* Solde restant */
    .balance-box {
        padding: 10px 16px;
        border-radius: 6px;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        margin-bottom: 20px;
    }

    .balance-table { width: 100%; }
    .balance-label { font-size: 8.5pt; font-weight: 700; color: #c2410c; }
    .balance-value { font-size: 14pt; font-weight: 700; color: #c2410c; text-align: right; }

    /* ═══════════════════════════════════════════════════════════
    NOTES / CONDITIONS
    ════════════════════════════════════════════════════════════ */
    .notes-box {
        padding: 12px 16px;
        border-radius: 6px;
        border-left: 3px solid #c4956a;
        background: #faf7f4;
        margin-bottom: 16px;
    }

    .notes-label {
        font-size: 6.5pt;
        letter-spacing: 2.5px;
        text-transform: uppercase;
        color: #c4956a;
        font-weight: 700;
        margin-bottom: 5px;
        display: block;
    }

    .notes-text { font-size: 7.5pt; color: #6b7280; line-height: 1.7; }

    .terms-box {
        padding: 10px 14px;
        border-radius: 6px;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        margin-bottom: 16px;
    }

    /* ═══════════════════════════════════════════════════════════
    FOOTER FIXE
    ════════════════════════════════════════════════════════════ */
    .footer {
        position: fixed;
        bottom: 0; left: 0; right: 0;
        padding: 14px 52px 16px 52px;
        background: #ffffff;
        border-top: 1px solid #f3f4f6;
        z-index: 20;
    }

    .footer-accent-line {
        position: absolute;
        top: 0; left: 52px;
        width: 40px;
        height: 2px;
        background: #c4956a;
    }

    .footer-table { width: 100%; }
    .footer-brand { font-size: 7pt; color: #9ca3af; font-weight: 700; letter-spacing: 1px; }
    .footer-brand span { color: #c4956a; }
    .footer-page  { font-size: 7pt; color: #d1d5db; text-align: center; }
    .footer-legal { font-size: 6.5pt; color: #d1d5db; text-align: right; }

    /* ═══════════════════════════════════════════════════════════
    WATERMARK PAID
    ════════════════════════════════════════════════════════════ */
    .watermark-paid {
        position: fixed;
        top: 50%;
        left: 50%;
        font-size: 72pt;
        font-weight: 900;
        color: rgba(16, 185, 129, 0.06);
        text-transform: uppercase;
        letter-spacing: 12px;
        transform: translate(-50%, -50%) rotate(-28deg);
        z-index: 1;
        white-space: nowrap;
    }

    .watermark-cancelled {
        position: fixed;
        top: 50%;
        left: 50%;
        font-size: 72pt;
        font-weight: 900;
        color: rgba(239, 68, 68, 0.06);
        text-transform: uppercase;
        letter-spacing: 8px;
        transform: translate(-50%, -50%) rotate(-28deg);
        z-index: 1;
        white-space: nowrap;
    }
</style>
</head>
<body>
<div class="page">

    <!-- ─── Éléments décoratifs background ─── -->
    <div class="side-bar"></div>
    <div class="deco-circle-tr"></div>
    <div class="deco-circle-bl"></div>

    <!-- ─── Pattern SVG diagonal (tresses géométriques) ─── -->
    <div class="bg-pattern">
        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <!-- Pattern losanges entrelacés, référence subtile aux tresses -->
                <pattern id="braid" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                    <!-- Losange 1 -->
                    <path d="M20 2 L38 20 L20 38 L2 20 Z" fill="none" stroke="#c4956a" stroke-width="0.4" opacity="0.18"/>
                    <!-- Croix centrale -->
                    <path d="M20 8 L20 32 M8 20 L32 20" fill="none" stroke="#d4af37" stroke-width="0.3" opacity="0.12"/>
                    <!-- Points coins -->
                    <circle cx="20" cy="2" r="1.2" fill="#c4956a" opacity="0.15"/>
                    <circle cx="38" cy="20" r="1.2" fill="#c4956a" opacity="0.15"/>
                    <circle cx="20" cy="38" r="1.2" fill="#c4956a" opacity="0.15"/>
                    <circle cx="2" cy="20" r="1.2" fill="#c4956a" opacity="0.15"/>
                </pattern>

                <!-- Pattern secondaire: hexagones légers dans le coin -->
                <pattern id="hex" x="0" y="0" width="34" height="39" patternUnits="userSpaceOnUse">
                    <path d="M17 1 L32 9.5 L32 29.5 L17 38 L2 29.5 L2 9.5 Z"
                          fill="none" stroke="#0d0d1a" stroke-width="0.25" opacity="0.04"/>
                </pattern>
            </defs>

            <!-- Fond entier avec le losange -->
            <rect width="100%" height="100%" fill="url(#braid)"/>

            <!-- Zone hexagones: coin supérieur droit (raffinement) -->
            <rect x="480" y="0" width="120" height="180" fill="url(#hex)" opacity="0.5"/>

            <!-- Bande subtile dorée: 1/3 supérieur -->
            <rect x="0" y="0" width="100%" height="80" fill="url(#braid)" opacity="0.4"/>

            <!-- Dégradé de masque: le pattern s'estompe vers le bas -->
            <defs>
                <linearGradient id="fade" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%"   stop-color="white" stop-opacity="0"/>
                    <stop offset="50%"  stop-color="white" stop-opacity="0"/>
                    <stop offset="100%" stop-color="white" stop-opacity="0.85"/>
                </linearGradient>
            </defs>
            <rect width="100%" height="100%" fill="url(#fade)"/>
        </svg>
    </div>

    <!-- ─── Watermarks ─── -->
    @if($invoice->status->value === 'paid')
        <div class="watermark-paid">PAYÉE</div>
    @elseif($invoice->status->value === 'cancelled')
        <div class="watermark-cancelled">ANNULÉE</div>
    @endif

    <!-- ─── Footer fixe ─── -->
    <div class="footer">
        <div class="footer-accent-line"></div>
        <table class="footer-table"><tr>
            <td class="footer-brand">
                <span>Patricia Braids</span> Studio · {{ $invoice->invoice_number }}
            </td>
            <td class="footer-page">Document confidentiel</td>
            <td class="footer-legal">
                @if(!empty($settings['business_siret']))SIRET {{ $settings['business_siret'] }} · @endif
                Généré le {{ now()->locale('fr')->isoFormat('D MMM YYYY') }}
            </td>
        </tr></table>
    </div>

    <!-- ─────────────────── CONTENU ─────────────────── -->
    <div class="content">

        {{-- ══ HEADER ══ --}}
        <table class="header-table"><tr>
            {{-- Gauche: Marque --}}
            <td style="width:55%; vertical-align:top;">
                <div class="brand-name">Patricia Braids</div>
                <div class="brand-tagline">Studio Premium &nbsp;·&nbsp; Création & Excellence</div>
                <div class="brand-info">
                    {{ $settings['business_address'] ?? 'Rue des Tessan, 97490 Ste-Clotilde' }}<br>
                    {{ $settings['business_phone'] ?? '+262 692 022 728' }} &nbsp;·&nbsp;
                    {{ $settings['business_email'] ?? 'contact@patricia.re' }}
                    @if(!empty($settings['business_siret']))<br>SIRET : {{ $settings['business_siret'] }}@endif
                </div>
            </td>
            {{-- Droite: Identifiant facture --}}
            <td style="width:45%; text-align:right; vertical-align:top;">
                <span class="invoice-word">Facture</span>
                <span class="invoice-number-big">{{ $invoice->invoice_number }}</span>
                <span class="invoice-date-label">Émise le {{ $invoice->issue_date->locale('fr')->isoFormat('D MMMM YYYY') }}</span>
            </td>
        </tr></table>

        {{-- Ligne déco --}}
        <div class="header-divider">
            <div class="header-divider-line"></div>
            <div class="header-divider-accent"></div>
        </div>

        {{-- ══ STATUS ══ --}}
        <div class="status-bar">
            @php
            $statusClass = match($invoice->status->value) {
                'paid'      => 'status-paid',
                'sent'      => 'status-sent',
                'overdue'   => 'status-overdue',
                'cancelled' => 'status-cancelled',
                default     => 'status-draft',
            };
            $statusLabel = match($invoice->status->value) {
                'paid'      => '✓ Payée',
                'sent'      => '↗ Envoyée',
                'overdue'   => '⚠ En retard',
                'cancelled' => '✕ Annulée',
                default     => '◎ Brouillon',
            };
            @endphp
            <span class="status-pill {{ $statusClass }}">{{ $statusLabel }}</span>
            @if($invoice->is_overdue && $invoice->status->value !== 'cancelled')
                <span class="due-warning">Échéance dépassée</span>
            @endif
        </div>

        {{-- ══ PARTIES ══ --}}
        <table class="parties-table"><tr>
            {{-- Émetteur (fond sombre) --}}
            <td>
                <div class="party-box party-box-left">
                    <div class="party-label party-label-light">De</div>
                    <div class="party-name party-name-light">Patricia Braids Studio</div>
                    <div class="party-detail party-detail-light">
                        {{ $settings['business_address'] ?? '12 rue des Tresses' }}<br>
                        {{ $settings['business_city'] ?? '75001 Paris' }}<br>
                        {{ $settings['business_email'] ?? 'contact@patricia-braids.fr' }}
                    </div>
                </div>
            </td>
            {{-- Client (fond crème) --}}
            <td>
                <div class="party-box party-box-right">
                    <div class="party-label party-label-dark">Facturé à</div>
                    <div class="party-name party-name-dark">
                        {{ $invoice->client_snapshot['name'] ?? $invoice->client->full_name }}
                    </div>
                    <div class="party-detail party-detail-dark">
                        {{ $invoice->client_snapshot['email'] ?? $invoice->client->email }}<br>
                        @if(!empty($invoice->client_snapshot['phone']))
                            {{ $invoice->client_snapshot['phone'] }}<br>
                        @endif
                        @if(!empty($invoice->client_snapshot['address']))
                            {{ $invoice->client_snapshot['address'] }}<br>
                            {{ $invoice->client_snapshot['postal_code'] }} {{ $invoice->client_snapshot['city'] }}
                            @if(!empty($invoice->client_snapshot['country'])), {{ $invoice->client_snapshot['country'] }}@endif
                        @endif
                    </div>
                </div>
            </td>
        </tr></table>

        {{-- ══ META DATES ══ --}}
        <table class="meta-table"><tr>
            <td>
                <div class="meta-cell meta-cell-accent">
                    <span class="meta-cell-label">N° Facture</span>
                    <span class="meta-cell-value" style="color:#c4956a;">{{ $invoice->invoice_number }}</span>
                </div>
            </td>
            <td>
                <div class="meta-cell">
                    <span class="meta-cell-label">Date d'émission</span>
                    <span class="meta-cell-value">{{ $invoice->issue_date->locale('fr')->isoFormat('D MMM YYYY') }}</span>
                </div>
            </td>
            <td>
                <div class="meta-cell {{ $invoice->is_overdue ? '' : '' }}" style="{{ $invoice->is_overdue ? 'border-left-color: #ef4444;' : '' }}">
                    <span class="meta-cell-label" style="{{ $invoice->is_overdue ? 'color:#ef4444;' : '' }}">Échéance</span>
                    <span class="meta-cell-value" style="{{ $invoice->is_overdue ? 'color:#dc2626;' : '' }}">
                        {{ $invoice->due_date->locale('fr')->isoFormat('D MMM YYYY') }}
                    </span>
                </div>
            </td>
            <td>
                <div class="meta-cell">
                    <span class="meta-cell-label">Type</span>
                    <span class="meta-cell-value">
                        @php $types = ['order'=>'Commande','appointment'=>'Rendez-vous','manual'=>'Manuel']; @endphp
                        {{ $types[$invoice->type] ?? ucfirst($invoice->type) }}
                        @if($invoice->order) · {{ $invoice->order->order_number }}@endif
                    </span>
                </div>
            </td>
        </tr></table>

        {{-- ══ LIGNES ══ --}}
        <span class="section-title">Détail des prestations</span>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="text-align:left; width:46%;">Description</th>
                    <th class="center" style="width:8%;">Qté</th>
                    <th class="right" style="width:14%;">Prix unit. HT</th>
                    @if($invoice->items->sum('discount_percent') > 0)
                    <th class="center" style="width:8%;">Remise</th>
                    @endif
                    <th class="right" style="width:14%;">Total HT</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>
                        <div class="td-desc">{{ $item->description }}</div>
                        @if($item->details)
                            <div class="td-desc-sub">{{ $item->details }}</div>
                        @endif
                    </td>
                    <td class="td-center">{{ $item->quantity }}</td>
                    <td class="td-right">{{ number_format($item->unit_price, 2, ',', ' ') }} €</td>
                    @if($invoice->items->sum('discount_percent') > 0)
                    <td class="td-discount">
                        {{ $item->discount_percent > 0 ? '-' . $item->discount_percent . '%' : '—' }}
                    </td>
                    @endif
                    <td class="td-total">{{ number_format($item->total, 2, ',', ' ') }} €</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- ══ TOTAUX ══ --}}
        <div style="width:100%; overflow:hidden; margin-bottom:24px;">
            <div style="float:right; width:44%;">

                <table class="totals-row-table">
                    <tr>
                        <td class="totals-label">Sous-total HT</td>
                        <td class="totals-value">{{ number_format($invoice->subtotal, 2, ',', ' ') }} €</td>
                    </tr>
                    @if($invoice->discount_amount > 0)
                    <tr>
                        <td class="totals-discount-label">Remise</td>
                        <td class="totals-discount-value">− {{ number_format($invoice->discount_amount, 2, ',', ' ') }} €</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="totals-label">TVA ({{ $invoice->tax_rate }}%)</td>
                        <td class="totals-value">{{ number_format($invoice->tax_amount, 2, ',', ' ') }} €</td>
                    </tr>
                </table>

                <div class="grand-total-box" style="overflow:hidden;">
                    <span class="grand-total-label">Total TTC</span>
                    <span class="grand-total-value">{{ number_format($invoice->total, 2, ',', ' ') }} €</span>
                </div>

            </div>
        </div>

        {{-- ══ PAIEMENTS REÇUS ══ --}}
        @if($invoice->payments && $invoice->payments->count() > 0)
        <div class="payments-section">
            <span class="section-title">Paiements reçus</span>
            @foreach($invoice->payments as $payment)
            <div class="payment-row">
                <table class="payment-row-table"><tr>
                    <td>
                        <div class="payment-method">{{ $payment->method->label() }}</div>
                        @if($payment->notes)<div class="payment-note">{{ $payment->notes }}</div>@endif
                    </td>
                    <td class="payment-date">{{ $payment->paid_at->locale('fr')->isoFormat('D MMM YYYY') }}</td>
                    <td class="payment-amount">{{ number_format($payment->amount, 2, ',', ' ') }} €</td>
                </tr></table>
            </div>
            @endforeach
        </div>
        @endif

        {{-- ══ SOLDE RESTANT ══ --}}
        @if($invoice->amount_due > 0 && !in_array($invoice->status->value, ['cancelled']))
        <div class="balance-box">
            <table class="balance-table"><tr>
                <td class="balance-label">Solde restant dû</td>
                <td class="balance-value">{{ number_format($invoice->amount_due, 2, ',', ' ') }} €</td>
            </tr></table>
        </div>
        @endif

        {{-- ══ NOTES ══ --}}
        @if($invoice->notes)
        <div class="notes-box">
            <span class="notes-label">Notes</span>
            <div class="notes-text">{{ $invoice->notes }}</div>
        </div>
        @endif

        {{-- ══ CONDITIONS ══ --}}
        @if($invoice->terms || !empty($settings['invoice_terms']))
        <div class="terms-box">
            <span class="notes-label" style="color:#9ca3af; letter-spacing:2px;">Conditions de paiement</span>
            <div class="notes-text">{{ $invoice->terms ?? $settings['invoice_terms'] }}</div>
        </div>
        @endif

        {{-- ══ MENTION LÉGALE ══ --}}
        <div style="margin-top:12px; padding-top:12px; border-top:1px dashed #e5e7eb;">
            <p style="font-size:6.5pt; color:#d1d5db; text-align:center; letter-spacing:1px;">
                En cas de retard de paiement, des pénalités de retard seront appliquées conformément à la loi en vigueur.
                &nbsp;·&nbsp; TVA non applicable, art. 293 B du CGI — si applicable selon votre situation.
            </p>
        </div>

    </div>{{-- /content --}}
</div>{{-- /page --}}
</body>
</html>