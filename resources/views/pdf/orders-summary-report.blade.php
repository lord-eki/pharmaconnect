<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Orders Summary Report - {{ $supplier->company_name }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #1e40af;
            font-size: 24px;
        }
        .header h2 {
            margin: 5px 0;
            color: #6b7280;
            font-size: 14px;
            font-weight: normal;
        }
        .date-range {
            margin: 20px 0;
            padding: 15px;
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            border-radius: 5px;
        }
        .date-range p {
            margin: 5px 0;
            font-size: 11px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            text-align: center;
        }
        .stat-card.success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .stat-card.warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        .stat-card.info {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }
        .stat-card .label {
            font-size: 10px;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        .stat-card .value {
            font-size: 20px;
            font-weight: bold;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e5e7eb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #3b82f6;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .amount {
            text-align: right;
            font-weight: bold;
            color: #059669;
        }
        .percentage {
            font-size: 9px;
            color: #6b7280;
            font-style: italic;
        }
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            font-size: 9px;
            color: #6b7280;
        }
        .chart-placeholder {
            height: 150px;
            background-color: #f9fafb;
            border: 1px dashed #d1d5db;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Orders Summary Report</h1>
        <h2>{{ $supplier->company_name }}</h2>
        <p style="font-size: 10px; color: #6b7280;">
            {{ $supplier->email }} | {{ $supplier->phone }}
        </p>
        <p style="font-size: 9px; margin-top: 10px;">
            Generated on: {{ $generatedAt->format('F d, Y H:i:s') }}
        </p>
    </div>

    @if($dateRange['from'] && $dateRange['to'])
    <div class="date-range">
        <p><strong>Report Period:</strong></p>
        <p>From: {{ $dateRange['from']->format('F d, Y') }}</p>
        <p>To: {{ $dateRange['to']->format('F d, Y') }}</p>
        <p>Duration: {{ $dateRange['from']->diffInDays($dateRange['to']) }} days</p>
    </div>
    @endif

    <div class="stats-grid">
        <div class="stat-card info">
            <div class="label">Total Orders</div>
            <div class="value">{{ $stats['total_orders'] }}</div>
        </div>
        <div class="stat-card success">
            <div class="label">Total Revenue</div>
            <div class="value">KES {{ number_format($stats['total_revenue'], 2) }}</div>
        </div>
        <div class="stat-card">
            <div class="label">Average Order Value</div>
            <div class="value">KES {{ number_format($stats['average_order_value'], 2) }}</div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card success">
            <div class="label">Delivered Orders</div>
            <div class="value">{{ $stats['delivered_orders'] }}</div>
            <div class="percentage">
                {{ $stats['total_orders'] > 0 ? number_format(($stats['delivered_orders'] / $stats['total_orders']) * 100, 1) : 0 }}% of total
            </div>
        </div>
        <div class="stat-card warning">
            <div class="label">Pending Orders</div>
            <div class="value">{{ $stats['pending_orders'] }}</div>
            <div class="percentage">
                {{ $stats['total_orders'] > 0 ? number_format(($stats['pending_orders'] / $stats['total_orders']) * 100, 1) : 0 }}% of total
            </div>
        </div>
        <div class="stat-card info">
            <div class="label">Shipped Orders</div>
            <div class="value">{{ $stats['shipped_orders'] }}</div>
            <div class="percentage">
                {{ $stats['total_orders'] > 0 ? number_format(($stats['shipped_orders'] / $stats['total_orders']) * 100, 1) : 0 }}% of total
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="label">Total Items Sold</div>
            <div class="value">{{ $stats['total_items'] }}</div>
        </div>
        <div class="stat-card">
            <div class="label">Unique Physicians</div>
            <div class="value">{{ $stats['unique_physicians'] }}</div>
        </div>
        <div class="stat-card">
            <div class="label">Unique Patients</div>
            <div class="value">{{ $stats['unique_patients'] }}</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Orders by Status</div>
        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th style="text-align: center;">Number of Orders</th>
                    <th style="text-align: right;">Total Revenue</th>
                    <th style="text-align: right;">Percentage</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ordersByStatus as $status => $data)
                <tr>
                    <td style="text-transform: capitalize; font-weight: bold;">{{ $status }}</td>
                    <td style="text-align: center;">{{ $data['count'] }}</td>
                    <td class="amount">KES {{ number_format($data['revenue'], 2) }}</td>
                    <td style="text-align: right;">
                        {{ $stats['total_orders'] > 0 ? number_format(($data['count'] / $stats['total_orders']) * 100, 1) : 0 }}%
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background-color: #f3f4f6; font-weight: bold;">
                    <td>TOTAL</td>
                    <td style="text-align: center;">{{ $stats['total_orders'] }}</td>
                    <td class="amount">KES {{ number_format($stats['total_revenue'], 2) }}</td>
                    <td style="text-align: right;">100%</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if($monthlyBreakdown->count() > 0)
    <div class="section">
        <div class="section-title">Monthly Breakdown</div>
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th style="text-align: center;">Number of Orders</th>
                    <th style="text-align: right;">Revenue</th>
                    <th style="text-align: right;">Avg. Order Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthlyBreakdown as $month => $data)
                <tr>
                    <td style="font-weight: bold;">
                        {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}
                    </td>
                    <td style="text-align: center;">{{ $data['count'] }}</td>
                    <td class="amount">KES {{ number_format($data['revenue'], 2) }}</td>
                    <td class="amount">
                        KES {{ number_format($data['revenue'] / $data['count'], 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="section">
        <div class="section-title">Performance Metrics</div>
        <table>
            <thead>
                <tr>
                    <th>Metric</th>
                    <th style="text-align: right;">Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Fulfillment Rate</td>
                    <td style="text-align: right; font-weight: bold;">
                        {{ $stats['total_orders'] > 0 ? number_format(($stats['delivered_orders'] / $stats['total_orders']) * 100, 2) : 0 }}%
                    </td>
                </tr>
                <tr>
                    <td>Average Items per Order</td>
                    <td style="text-align: right; font-weight: bold;">
                        {{ $stats['total_orders'] > 0 ? number_format($stats['total_items'] / $stats['total_orders'], 2) : 0 }}
                    </td>
                </tr>
                <tr>
                    <td>Average Revenue per Physician</td>
                    <td style="text-align: right; font-weight: bold;">
                        KES {{ $stats['unique_physicians'] > 0 ? number_format($stats['total_revenue'] / $stats['unique_physicians'], 2) : 0 }}
                    </td>
                </tr>
                <tr>
                    <td>Average Revenue per Patient</td>
                    <td style="text-align: right; font-weight: bold;">
                        KES {{ $stats['unique_patients'] > 0 ? number_format($stats['total_revenue'] / $stats['unique_patients'], 2) : 0 }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p><strong>{{ $supplier->company_name }}</strong></p>
        <p>{{ $supplier->address ?? '' }} | {{ $supplier->city ?? '' }}, {{ $supplier->county ?? '' }}</p>
        <p>Tax PIN: {{ $supplier->tax_pin ?? 'N/A' }}</p>
        <p style="margin-top: 10px;">This is a computer-generated report from {{ config('app.name') }}</p>
        <p>© {{ now()->year }} {{ $supplier->company_name }}. All rights reserved.</p>
    </div>
</body>
</html>