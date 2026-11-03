<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local Purchase Order - {{ $order->order_number }}</title>
    <link rel="stylesheet" href="/resources/css/supplier_lpo.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-top">
                <table>
                    <tr>
                        <td class="company-info">
                            <h1>{{ e($company_info['company_name'] ?? 'PharmaConnect') }}</h1>
                            <p>{{ e($company_info['company_address'] ?? 'N/A') }}</p>
                            <p>Tel: {{ e($company_info['company_phone'] ?? '+254700000000') }} | Email: {{ e($company_info['company_email'] ?? 'info@pharmaconnect.com') }}</p>
                            <p>Website: {{ e($company_info['company_website'] ?? 'www.pharmaconnect.com') }}</p>
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
                @foreach($order->items->where('is_delivery_fee', false)->values() as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ e($item->medicine->brand_name ?? 'N/A') }}</strong>
                        <br><small style="color: #666;"> {{ e($item->medicine->generic_name) }}</small>
                        @if($item->notes ?? false)
                        <br><small style="color: #666;">{{ e($item->notes) }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($item->quantity, 0) }} {{ $item->unit ?? 'pcs' }}</td>
                    <td class="text-right">KES {{ number_format($item->supplier_price ?? 0, 2) }}</td>
                    <td class="text-right">KES {{ number_format($item->tax_amount ?? 0, 2) }}</td>
                    <td class="text-right"><strong>KES {{ number_format(($item->supplier_price ?? 0) * $item->quantity, 2) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            <div class="totals-wrapper">
                <table class="totals-table">
                    @php
                        $medicineItems = $order->items->where('is_delivery_fee', false);
                       $subtotal = $medicineItems->sum(fn ($item) => ($item->supplier_price ?? 0) * $item->quantity);
                       $taxAmount = $medicineItems->sum(function($item) {
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
+                        <td class="amount">KES {{ number_format($subtotal + $taxAmount + $shippingCost, 2) }}</td>
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