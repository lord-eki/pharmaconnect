<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local Purchase Order - {{ $order->order_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #333;
        }
        
        .container {
            padding: 20px;
        }
        
        .header {
            border-bottom: 3px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header-top {
            width: 100%;
            margin-bottom: 10px;
        }
        
        .header-top table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .company-info {
            width: 60%;
            vertical-align: top;
        }
        
        .company-info h1 {
            color: #2563eb;
            font-size: 24pt;
            margin-bottom: 5px;
        }
        
        .company-info p {
            font-size: 10pt;
            color: #666;
            margin-bottom: 2px;
        }
        
        .lpo-title {
            width: 40%;
            text-align: right;
            vertical-align: top;
        }
        
        .lpo-title h2 {
            font-size: 28pt;
            color: #1e40af;
            font-weight: bold;
            margin: 0;
        }
        
        .lpo-title p {
            font-size: 12pt;
            color: #666;
            margin-top: 5px;
        }
        
        .details-section {
            width: 100%;
            margin-bottom: 20px;
        }
        
        .details-section table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .details-col {
            width: 50%;
            padding: 5px;
            vertical-align: top;
        }
        
        .info-box {
            border: 1px solid #e5e7eb;
            padding: 15px;
            background-color: #f9fafb;
            border-radius: 5px;
            min-height: 150px;
        }
        
        .info-box h3 {
            font-size: 12pt;
            color: #2563eb;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .info-box p {
            margin-bottom: 5px;
            font-size: 10pt;
        }
        
        .info-box strong {
            color: #111;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .items-table thead {
            background-color: #2563eb;
            color: white;
        }
        
        .items-table th {
            padding: 10px;
            text-align: left;
            font-size: 10pt;
            font-weight: bold;
        }
        
        .items-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10pt;
        }
        
        .items-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .totals-section {
            margin-top: 20px;
            width: 100%;
        }
        
        .totals-wrapper {
            width: 100%;
        }
        
        .totals-wrapper table {
            width: 40%;
            margin-left: 60%;
            border-collapse: collapse;
        }
        
        .totals-table td {
            padding: 8px 10px;
            font-size: 10pt;
        }
        
        .totals-table .label {
            text-align: right;
            font-weight: bold;
            color: #666;
        }
        
        .totals-table .amount {
            text-align: right;
            width: 40%;
        }
        
        .totals-table .grand-total {
            border-top: 2px solid #2563eb;
            font-weight: bold;
            font-size: 12pt;
            color: #2563eb;
            padding-top: 10px;
        }
        
        .notes-section {
            margin-top: 30px;
            padding: 15px;
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            border-radius: 5px;
        }
        
        .notes-section h4 {
            color: #92400e;
            margin-bottom: 8px;
            font-size: 11pt;
        }
        
        .notes-section p {
            color: #78350f;
            font-size: 10pt;
        }
        
        .terms-section {
            margin-top: 30px;
            padding: 15px;
            background-color: #f3f4f6;
            border-radius: 5px;
        }
        
        .terms-section h4 {
            color: #1f2937;
            margin-bottom: 10px;
            font-size: 11pt;
        }
        
        .terms-section ul {
            margin-left: 20px;
            font-size: 9pt;
            color: #4b5563;
        }
        
        .terms-section li {
            margin-bottom: 5px;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
        }
        
        .signatures {
            width: 100%;
            margin-top: 40px;
        }
        
        .signatures table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .signature-box {
            width: 50%;
            text-align: center;
            padding: 20px;
            vertical-align: top;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            margin: 40px 30px 10px 30px;
        }
        
        .signature-label {
            font-size: 10pt;
            color: #666;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 9pt;
            font-weight: bold;
        }
        
        .badge-warning { background-color: #fef3c7; color: #92400e; }
        .badge-info { background-color: #dbeafe; color: #1e40af; }
        .badge-success { background-color: #d1fae5; color: #065f46; }
        .badge-primary { background-color: #e0e7ff; color: #3730a3; }
        .badge-danger { background-color: #fee2e2; color: #991b1b; }
        
        .section-title {
            margin-top: 20px;
            margin-bottom: 10px;
            color: #2563eb;
            font-size: 12pt;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-top">
                <table>
                    <tr>
                        <td class="company-info">
                            <h1>{{ e($company_info['name'] ?? 'PharmaConnect') }}</h1>
                            <p>{{ e($company_info['address'] ?? 'N/A') }}</p>
                            <p>Tel: {{ e($company_info['phone'] ?? '+254700000000') }} | Email: {{ e($company_info['email'] ?? 'info@pharmaconnect.com') }}</p>
                            <p>Website: {{ e($company_info['website'] ?? 'www.pharmaconnect.com') }}</p>
                        </td>
                        <td class="lpo-title">
                            <h2>LPO</h2>
                            <p>Local Purchase Order</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Order Details -->
        <div class="details-section">
            <table>
                <tr>
                    <td class="details-col">
                        <div class="info-box">
                            <h3>Order Information</h3>
                            <p><strong>LPO Number:</strong> {{ $order->order_number ?? 'N/A' }}</p>
                            <p><strong>Order Date:</strong> {{ $order->ordered_at ? $order->ordered_at->format('F d, Y') : 'N/A' }}</p>
                            <p><strong>Status:</strong> 
                                <span class="badge badge-{{ 
                                    $order->status === 'pending_review' ? 'warning' :
                                    ($order->status === 'sent_to_supplier' ? 'info' :
                                    ($order->status === 'delivered' ? 'success' :
                                    ($order->status === 'cancelled' ? 'danger' : 'primary')))
                                }}">
                                    {{ ucwords(str_replace('_', ' ', $order->status ?? 'unknown')) }}
                                </span>
                            </p>
                            @if($order->expected_delivery)
                            <p><strong>Expected Delivery:</strong> {{ $order->expected_delivery->format('F d, Y') }}</p>
                            @endif
                        </div>
                    </td>
                    <td class="details-col">
                        <div class="info-box">
                            <h3>Supplier Details</h3>
                            <p><strong>{{ e($order->supplier->company_name ?? 'N/A') }}</strong></p>
                            @if($order->supplier && $order->supplier->contact_person)
                            <p>Attn: {{ e($order->supplier->contact_person) }}</p>
                            @endif
                            @if($order->supplier && $order->supplier->email)
                            <p>Email: {{ e($order->supplier->email) }}</p>
                            @endif
                            @if($order->supplier && $order->supplier->phone)
                            <p>Phone: {{ e($order->supplier->phone) }}</p>
                            @endif
                            @if($order->supplier && $order->supplier->address)
                            <p>{{ e($order->supplier->address) }}</p>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Order Items -->
        <h3 class="section-title">Order Items</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 40%;">Item Description</th>
                    <th style="width: 10%;" class="text-center">Qty</th>
                    <th style="width: 15%;" class="text-right">Unit Price</th>
                    <th style="width: 10%;" class="text-right">Tax</th>
                    <th style="width: 20%;" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ e($item->medicine->name ?? 'N/A') }}</strong>
                        @if($item->medicine && $item->medicine->sku)
                        <br><small style="color: #666;">SKU: {{ e($item->medicine->sku) }}</small>
                        @endif
                        @if($item->notes ?? false)
                        <br><small style="color: #666;">{{ e($item->notes) }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($item->quantity, 0) }} {{ $item->unit ?? 'pcs' }}</td>
                    <td class="text-right">KES {{ number_format($item->unit_price ?? 0, 2) }}</td>
                    <td class="text-right">KES {{ number_format($item->tax_amount ?? 0, 2) }}</td>
                    <td class="text-right"><strong>KES {{ number_format($item->total_price ?? 0, 2) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            <div class="totals-wrapper">
                <table class="totals-table">
                    @php
                        $subtotal = $order->items->sum('total_price');
                        $taxAmount = $order->items->sum(function($item) {
                            return $item->tax_amount ?? 0;
                        });
                        $shippingCost = $order->delivery->delivery_fee ?? 0;
                    @endphp
                    <tr>
                        <td class="label">Subtotal:</td>
                        <td class="amount">KES {{ number_format($subtotal, 2) }}</td>
                    </tr>
                    @if($taxAmount > 0)
                    <tr>
                        <td class="label">Tax (16% VAT):</td>
                        <td class="amount">KES {{ number_format($taxAmount, 2) }}</td>
                    </tr>
                    @endif
                    @if($shippingCost > 0)
                    <tr>
                        <td class="label">Shipping:</td>
                        <td class="amount">KES {{ number_format($shippingCost, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="grand-total">
                        <td class="label">Grand Total:</td>
                        <td class="amount">KES {{ number_format($order->total_amount, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Notes -->
        @if($order->notes)
        <div class="notes-section">
            <h4>Special Instructions / Notes</h4>
            <p>{{ e($order->notes) }}</p>
        </div>
        @endif

        <!-- Terms and Conditions -->
        <div class="terms-section">
            <h4>Terms and Conditions</h4>
            <ul>
                <li>Please confirm receipt of this purchase order within 24 hours</li>
                <li>Delivery must be made by the expected delivery date specified above</li>
                <li>All items must match the specifications provided</li>
                <li>Payment will be processed within 30 days of delivery and invoice receipt</li>
                <li>Goods must be delivered to the address specified on this order</li>
                <li>Any discrepancies must be reported immediately upon delivery</li>
            </ul>
        </div>

        <!-- Signatures -->
        <div class="signatures">
            <table>
                <tr>
                    <td class="signature-box">
                        <div class="signature-line"></div>
                        <p class="signature-label">Authorized By</p>
                        <p class="signature-label"><small>Name & Date</small></p>
                    </td>
                    <td class="signature-box">
                        <div class="signature-line"></div>
                        <p class="signature-label">Supplier Confirmation</p>
                        <p class="signature-label"><small>Name & Date</small></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="text-align: center; font-size: 9pt; color: #666;">
                This is a computer-generated document. No signature is required.<br>
                Generated on {{ now()->format('F d, Y \a\t H:i') }}
            </p>
        </div>
    </div>
</body>
</html>