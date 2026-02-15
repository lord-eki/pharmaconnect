<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Report - {{ $supplier->company_name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.4;
            margin: 15px 30px 40px 30px;
            padding-bottom: 80px;
        }

        .header {
            background: linear-gradient(135deg, #ff6b35 0%, #ff8c42 100%);
            color: white;
            padding: 10px 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .header h1 {
            font-size: 24pt;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .header p {
            font-size: 11pt;
            opacity: 0.95;
        }

        .company-info {
            margin-bottom: 15px;
            padding: 12px 15px;
            background-color: #fff5f0;
            border-left: 4px solid #ff6b35;
            border-radius: 3px;
        }

        .company-info h2 {
            color: #ff6b35;
            font-size: 14pt;
            margin-bottom: 8px;
        }

        .company-info p {
            font-size: 10pt;
            color: #666;
        }

        .summary-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }

        .summary-box {
            display: table-cell;
            width: 25%;
            padding: 15px;
            text-align: center;
            background-color: #fff5f0;
            border: 2px solid #ff6b35;
            margin-right: 10px;
        }

        .summary-box:last-child {
            margin-right: 0;
        }

        .summary-box .label {
            font-size: 9pt;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .summary-box .value {
            font-size: 18pt;
            color: #ff6b35;
            font-weight: bold;
        }

        .summary-box .currency {
            font-size: 10pt;
            color: #999;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .orders-table thead {
            background-color: #ff6b35;
            color: white;
        }

        .orders-table thead th {
            padding: 12px 8px;
            text-align: left;
            font-size: 9pt;
            font-weight: 600;
            text-transform: uppercase;
            border-bottom: 3px solid #ff8c42;
        }

        .orders-table tbody tr {
            border-bottom: 1px solid #e0e0e0;
            page-break-inside: avoid;
        }

        .orders-table tbody tr:nth-child(even) {
            background-color: #fff5f0;
        }

        .orders-table tbody tr:hover {
            background-color: #ffe8dc;
        }

        .orders-table tbody td {
            padding: 10px 8px;
            font-size: 9pt;
            color: #333;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 8pt;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-confirmed {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .status-processing {
            background-color: #fff3e0;
            color: #f57c00;
        }

        .status-shipped {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        .status-delivered {
            background-color: #c8e6c9;
            color: #2e7d32;
            font-weight: bold;
        }

        .status-cancelled {
            background-color: #ffebee;
            color: #c62828;
        }

        .status-pending {
            background-color: #fff9c4;
            color: #f57f17;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #ff6b35;
            text-align: center;
            color: #666;
            font-size: 8pt;
            position: fixed;
            bottom: 30px;
            left: 30px;
            right: 30px;
        }

        .footer .generated-date {
            color: #ff6b35;
            font-weight: 600;
        }

        .amount {
            font-weight: 600;
            color: #ff6b35;
        }

        .order-number {
            font-weight: 600;
            color: #333;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
        }

        @media print {
            .header {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Orders Report</h1>
        <p>Comprehensive overview of order transactions and performance</p>
    </div>

    <div class="company-info">
        <h2>{{ str_replace('  ', ' ', $supplier->company_name) }}</h2>
        <p>Report Generated: <strong>{{ $generatedAt->format('F d, Y \a\t h:i A') }}</strong></p>
    </div>

    <div class="summary-section">
        <div class="summary-box">
            <div class="label">Total Revenue</div>
            <div class="value">{{ number_format($totalRevenue, 0) }}</div>
            <div class="currency">KES</div>
        </div>
        <div class="summary-box">
            <div class="label">Total Orders</div>
            <div class="value">{{ $totalOrders }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Delivered</div>
            <div class="value">{{ $deliveredOrders }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Pending</div>
            <div class="value">{{ $pendingOrders }}</div>
        </div>
    </div>

    @if($orders->count() > 0)
        <table class="orders-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Order Number</th>
                    <th style="width: 12%;">Status</th>
                    <th style="width: 13%;">Amount</th>
                    <th style="width: 8%;">Items</th>
                    <th style="width: 17%;">Ordered Date</th>
                    <th style="width: 17%;">Expected Delivery</th>
                    <th style="width: 18%;">Delivered Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                    <tr>
                        <td class="order-number">{{ $order->order_number }}</td>
                        <td>
                            <span class="status-badge status-{{ $order->status }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td class="amount">KES {{ number_format($order->supplier_total, 2) }}</td>
                        <td style="text-align: center;">{{ $order->items->count() }}</td>
                        <td>{{ $order->ordered_at?->format('M d, Y H:i') ?? 'N/A' }}</td>
                        <td>{{ $order->expected_delivery?->format('M d, Y') ?? 'N/A' }}</td>
                        <td>
                            @if($order->delivered_at)
                                <span style="color: #2e7d32; font-weight: 600;">
                                    {{ $order->delivered_at->format('M d, Y H:i') }}
                                </span>
                            @else
                                <span style="color: #999; font-style: italic;">Not delivered</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="no-data">
            No orders found for the selected criteria.
        </div>
    @endif

    <div class="footer">
        <p>This report was automatically generated on <span class="generated-date">{{ $generatedAt->format('F d, Y \a\t h:i A') }}</span></p>
        <p>&copy; {{ date('Y') }} {{ $supplier->company_name }}. All rights reserved.</p>
    </div>
</body>
</html>