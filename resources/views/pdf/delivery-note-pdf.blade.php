<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Delivery Note - {{ $delivery->delivery_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #ff6b35;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #ff6b35;
            margin: 0;
            font-size: 24px;
        }
        .company-info {
            text-align: center;
            color: #666;
            font-size: 10px;
            margin-top: 10px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            background-color: #ff6b35;
            color: white;
            padding: 8px 12px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.info-table td {
            padding: 6px 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        table.info-table td:first-child {
            font-weight: bold;
            width: 35%;
        }
        table.items-table {
            margin-top: 10px;
        }
        table.items-table th {
            background-color: #f3f4f6;
            padding: 8px;
            text-align: left;
            border-bottom: 2px solid #ff6b35;
        }
        table.items-table td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        table.items-table tfoot td {
            font-weight: bold;
            padding: 8px;
        }
        table.items-table tfoot tr:last-child {
            border-top: 2px solid #ff6b35;
        }
        .order-section {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
        }
        .order-header {
            background-color: #f9fafb;
            padding: 8px;
            margin: -10px -10px 10px -10px;
            font-weight: bold;
            color: #374151;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
        }
        .signature-section {
            margin-top: 40px;
            display: table;
            width: 100%;
        }
        .signature-box {
            display: table-cell;
            width: 45%;
            padding: 10px;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 5px;
        }
        .note-box {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 10px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>DELIVERY NOTE</h1>
        <div class="company-info">
            @if($company_info ?? null)
                <strong>{{ $company_info['name'] ?? config('app.name') }}</strong><br>
                @if(isset($company_info['address'])){{ $company_info['address'] }}<br>@endif
                @if(isset($company_info['phone']))Tel: {{ $company_info['phone'] }} | @endif
                @if(isset($company_info['email']))Email: {{ $company_info['email'] }}@endif
            @else
                <strong>{{ config('app.name') }}</strong>
            @endif
        </div>
    </div>

    <div class="section">
        <div class="section-title">Delivery Information</div>
        <table class="info-table">
            <tr>
                <td>Delivery Number:</td>
                <td><strong>{{ $delivery->delivery_number }}</strong></td>
            </tr>
            @if($prescription)
            <tr>
                <td>Prescription Number:</td>
                <td>{{ $prescription->prescription_number }}</td>
            </tr>
            @endif
            @if($orders && $orders->count() > 0)
            <tr>
                <td>Order Number(s):</td>
                <td>{{ $orders->pluck('order_number')->join(', ') }}</td>
            </tr>
            @endif
            <tr>
                <td>Delivery Date:</td>
                <td>{{ $delivery->actual_delivery?->format('F d, Y \a\t H:i A') ?? ($delivery->delivered_at?->format('F d, Y \a\t H:i A') ?? now()->format('F d, Y \a\t H:i A')) }}</td>
            </tr>
            <tr>
                <td>Generated At:</td>
                <td>{{ $generated_at->format('F d, Y \a\t H:i A') }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Recipient Information</div>
        <table class="info-table">
            <tr>
                <td>Name:</td>
                <td>{{ $patient->full_name ?? $delivery->recipient_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Phone:</td>
                <td>{{ $patient->phone ?? $delivery->recipient_phone ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Email:</td>
                <td>{{ $patient->email ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Delivery Address:</td>
                <td>{{ $delivery->delivery_address }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Delivery Personnel</div>
        <table class="info-table">
            <tr>
                <td>Rider Name:</td>
                <td>{{ $rider?->full_name ?? 'N/A' }}</td>
            </tr>
            @if($rider)
            <tr>
                <td>Rider Phone:</td>
                <td>{{ $rider->phone ?? 'N/A' }}</td>
            </tr>
            @endif
            <tr>
                <td>Delivery Fee:</td>
                <td>KES {{ number_format($delivery->delivery_fee, 2) }}</td>
            </tr>
        </table>
    </div>

    @if($orders && $orders->count() > 0)
    <div class="section">
        <div class="section-title">Items Delivered</div>
        
        @foreach($orders as $order)
            @if($orders->count() > 1)
            <div class="order-section">
                <div class="order-header">
                    Order: {{ $order->order_number }}
                    @if($order->supplier)
                    - Supplier: {{ $order->supplier->name }}
                    @endif
                </div>
            @endif
            
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">#</th>
                        <th style="width: 50%;">Item Description</th>
                        <th style="width: 15%; text-align: center;">Quantity</th>
                        <th style="width: 25%; text-align: right;">Amount (KES)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $item->medicine->generic_name ?? $item->product_name ?? 'N/A' }}</strong>
                            @if($item->medicine && $item->medicine->brand_name)
                            <br><span style="font-size: 10px; color: #666;">Brand: {{ $item->medicine->brand_name }}</span>
                            @endif
                            @if($item->medicine && $item->medicine->strength)
                            <br><span style="font-size: 10px; color: #666;">Strength: {{ $item->medicine->strength }}</span>
                            @endif
                        </td>
                        <td style="text-align: center;">{{ $item->quantity }}</td>
                        <td style="text-align: right;">{{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                @if($orders->count() === 1 || $loop->last)
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right;">Subtotal:</td>
                        <td style="text-align: right;">{{ number_format($orders->sum('total_amount'), 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" style="text-align: right;">Delivery Fee:</td>
                        <td style="text-align: right;">{{ number_format($delivery->delivery_fee, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" style="text-align: right; font-size: 14px;">TOTAL:</td>
                        <td style="text-align: right; font-size: 14px;">{{ number_format($orders->sum('total_amount') + $delivery->delivery_fee, 2) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
            
            @if($orders->count() > 1)
            </div>
            @endif
        @endforeach
    </div>
    @endif

    @if($delivery->delivery_notes)
    <div class="note-box">
        <strong>Delivery Notes:</strong><br>
        {{ $delivery->delivery_notes }}
    </div>
    @endif

    <div class="signature-section">
        <div class="signature-box">
            <strong>Delivered By:</strong>
            <div class="signature-line">
                {{ $rider?->full_name ?? '___________________' }}<br>
                <span style="font-size: 10px;">Signature & Date</span>
            </div>
        </div>
        <div class="signature-box" style="text-align: right;">
            <strong>Received By:</strong>
            <div class="signature-line">
                ___________________<br>
                <span style="font-size: 10px;">Signature & Date</span>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>
            This is a computer-generated delivery note and is valid without signature.<br>
            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </p>
        <p style="margin-top: 10px; font-size: 9px;">
            Document generated on {{ now()->format('F d, Y \a\t H:i A') }}
        </p>
    </div>
</body>
</html>