<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Order #{{ $order->id }}</title>
    <link rel="stylesheet" href="/resources/css/order_print.css">
</head>
<body>

<div class="header">
    <div class="brand">
        <h1>{{$order->supplier->company_name}}</h1>
         <p>{{ $order->supplier->phone}}</p>
         <p> {{ $order->supplier->email }}</p>
        <p>{{ $order->supplier->address }} - {{$order->supplier->city}}</p>
    </div>
    <div class="order-meta">
        <div class="order-id">LPO #{{ $order->id }}</div>
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
    </div>
</div>
</div>

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
                <td>{{ $item->medicine?->generic_name ?? '—' }}</td>
                <td>{{ $item->medicine?->ppb_registration_number ??  '—' }}</td>
                <td>{{ number_format($item->supplier_price ?? 0, 2) }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format(($item->supplier_price ?? 0) * $item->quantity, 2) }}</td>
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

<div class="footer">
    <p>Generated on {{ now()->format('d M Y \a\t H:i') }} &nbsp;|&nbsp; Order #{{ $order->id }} &nbsp;|&nbsp; This document is system-generated.</p>
</div>

</body>
</html>