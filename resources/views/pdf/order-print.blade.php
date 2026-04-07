<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Order #{{ $order->id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 13px;
            color: #1a1a1a;
            background: #fff;
            padding: 40px;
        }

        /* ── Header ── */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #16a34a;
            padding-bottom: 20px;
            margin-bottom: 28px;
        }
        .brand h1 {
            font-size: 26px;
            color: #16a34a;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .brand p {
            color: #6b7280;
            font-size: 12px;
            margin-top: 2px;
        }
        .order-meta {
            text-align: right;
        }
        .order-meta .order-id {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
        }
        .order-meta .order-date {
            color: #6b7280;
            font-size: 12px;
            margin-top: 4px;
        }
        .badge {
            display: inline-block;
            margin-top: 8px;
            padding: 3px 12px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-pending    { background: #fef9c3; color: #854d0e; }
        .badge-confirmed  { background: #dcfce7; color: #166534; }
        .badge-processing { background: #dbeafe; color: #1e40af; }
        .badge-delivered  { background: #f0fdf4; color: #15803d; }
        .badge-rejected   { background: #fee2e2; color: #991b1b; }
        .badge-default    { background: #f3f4f6; color: #374151; }

        /* ── Two-column info grid ── */
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 28px;
            border-collapse: separate;
            border-spacing: 12px 0;
        }
        .info-col {
            display: table-cell;
            width: 50%;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            vertical-align: top;
        }
        .info-col h3 {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #6b7280;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .info-col p {
            font-size: 13px;
            color: #111827;
            margin-bottom: 4px;
            line-height: 1.5;
        }
        .info-col p strong {
            color: #374151;
        }

        /* ── Items table ── */
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #e5e7eb;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
        }
        table.items thead tr {
            background: #16a34a;
            color: #fff;
        }
        table.items thead th {
            padding: 10px 14px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        table.items thead th:last-child { text-align: right; }
        table.items tbody tr:nth-child(odd)  { background: #f9fafb; }
        table.items tbody tr:nth-child(even) { background: #fff; }
        table.items tbody td {
            padding: 10px 14px;
            font-size: 13px;
            color: #374151;
            border-bottom: 1px solid #f3f4f6;
        }
        table.items tbody td:last-child { text-align: right; font-weight: 600; }
        table.items tfoot tr { background: #f3f4f6; }
        table.items tfoot td {
            padding: 12px 14px;
            font-weight: 700;
            font-size: 14px;
            color: #111827;
        }
        table.items tfoot td:last-child {
            text-align: right;
            color: #16a34a;
        }

        /* ── Delivery & notes ── */
        .delivery-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 28px;
        }
        .delivery-box h3 {
            font-size: 13px;
            font-weight: 700;
            color: #166534;
            margin-bottom: 10px;
        }
        .delivery-box p {
            font-size: 13px;
            color: #374151;
            margin-bottom: 4px;
        }

        .notes-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 28px;
        }
        .notes-box h3 {
            font-size: 13px;
            font-weight: 700;
            color: #92400e;
            margin-bottom: 8px;
        }
        .notes-box p {
            font-size: 13px;
            color: #374151;
            white-space: pre-wrap;
        }

        /* ── Footer ── */
        .footer {
            border-top: 1px solid #e5e7eb;
            padding-top: 16px;
            text-align: center;
            color: #9ca3af;
            font-size: 11px;
        }

        @media print {
            body { padding: 20px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

{{-- ── HEADER ── --}}
<div class="header">
    <div class="brand">
        <h1>MediSupply</h1>
        <p>Order Invoice &amp; Packing Slip</p>
    </div>
    <div class="order-meta">
        <div class="order-id">Order #{{ $order->id }}</div>
        <div class="order-date">
            Placed: {{ $order->created_at?->format('d M Y, H:i') ?? '—' }}
        </div>
        @php
            $statusClasses = [
                'pending'    => 'badge-pending',
                'confirmed'  => 'badge-confirmed',
                'processing' => 'badge-processing',
                'delivered'  => 'badge-delivered',
                'rejected'   => 'badge-rejected',
            ];
            $cls = $statusClasses[$order->status] ?? 'badge-default';
        @endphp
        <span class="badge {{ $cls }}">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
    </div>
</div>

{{-- ── SUPPLIER / CUSTOMER INFO ── --}}
<div class="info-grid">
    {{-- Supplier --}}
    <div class="info-col">
        <h3>Supplier</h3>
        @if($order->supplier)
            <p><strong>{{ $order->supplier->name ?? '—' }}</strong></p>
            @if($order->supplier->address)
                <p>{{ $order->supplier->address }}</p>
            @endif
            @if($order->supplier->phone)
                <p>{{ $order->supplier->phone }}</p>
            @endif
            @if($order->supplier->email)
                <p>{{ $order->supplier->email }}</p>
            @endif
        @else
            <p>—</p>
        @endif
    </div>

    {{-- Customer / Delivery address --}}
    <div class="info-col">
        <h3>Customer / Delivery</h3>
        @if($order->customer)
            <p><strong>{{ $order->customer->name ?? '—' }}</strong></p>
            @if($order->customer->phone)
                <p>{{ $order->customer->phone }}</p>
            @endif
            @if($order->customer->email)
                <p>{{ $order->customer->email }}</p>
            @endif
        @endif
        @if($order->delivery_address)
            <p>{{ $order->delivery_address }}</p>
        @endif
        @if($order->expected_delivery)
            <p><strong>Expected:</strong> {{ \Carbon\Carbon::parse($order->expected_delivery)->format('d M Y, H:i') }}</p>
        @endif
    </div>
</div>

{{-- ── ORDER ITEMS ── --}}
<div class="section-title">Order Items</div>
<table class="items">
    <thead>
        <tr>
            <th>#</th>
            <th>Medicine</th>
            <th>SKU / Code</th>
            <th>Unit Price</th>
            <th>Qty</th>
            <th>Subtotal</th>
        </tr>
    </thead>
    <tbody>
        @forelse($order->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->medicine?->name ?? '—' }}</td>
                <td>{{ $item->medicine?->sku ?? $item->medicine?->code ?? '—' }}</td>
                <td>{{ number_format($item->unit_price ?? 0, 2) }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format(($item->unit_price ?? 0) * $item->quantity, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" style="text-align:center; color:#9ca3af; padding:20px;">No items found.</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4"></td>
            <td>Total</td>
            <td>
                {{ number_format($order->items->sum(fn($i) => ($i->unit_price ?? 0) * $i->quantity), 2) }}
            </td>
        </tr>
    </tfoot>
</table>

@if($order->delivery)
<div class="delivery-box">
    <h3>Delivery Details</h3>
    <p><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $order->delivery->status ?? '—')) }}</p>
    @if($order->delivery->rider)
        <p><strong>Rider:</strong> {{ $order->delivery->rider->user?->name ?? '—' }}</p>
        <p><strong>Rider Phone:</strong> {{ $order->delivery->rider->user?->phone ?? '—' }}</p>
    @endif
    @if($order->delivery->delivery_notes)
        <p><strong>Notes:</strong> {{ $order->delivery->delivery_notes }}</p>
    @endif
</div>
@endif

@if($order->notes)
<div class="notes-box">
    <h3>📋 Order Notes</h3>
    <p>{{ $order->notes }}</p>
</div>
@endif

<div class="footer">
    <p>Generated on {{ now()->format('d M Y \a\t H:i') }} &nbsp;|&nbsp; Order #{{ $order->id }} &nbsp;|&nbsp; This document is system-generated.</p>
</div>

</body>
</html>