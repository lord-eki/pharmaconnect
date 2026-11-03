<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local Purchase Order - {{ $order->order_number }}</title>
    <link rel="stylesheet" href="/resources/css/lpo.css"> 
</head>
<body>
    <div class="page-wrapper">
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-top">
                <table>
                    <tr>
                        <td class="company-info">
                            <h1>{{ $company_info['company_name'] ?? 'Pharmaconnect' }}</h1>
                            <p>{{ $company_info['company_address'] ?? 'P.O. Box 12345, Nairobi, Kenya' }}</p>
                            <p>Tel: {{ $company_info['company_phone'] ?? '+254 700 000 000' }} | Email: {{ $company_info['company_email'] ?? 'info@pharmaconnect.com' }}</p>
                            <p>Website: {{ $company_info['company_website'] ?? 'www.pharmaconnect.com' }}</p>
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
                            @if($order->prescription)
                            <p><strong>Prescription:</strong> {{ $order->prescription->prescription_number ?? 'N/A' }}</p>
                            @endif
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

        @if($order->prescription)
        <div class="details-section">
            <table>
                <tr>
                    <td class="details-col">
                        <div class="info-box">
                            <h3>Patient Information</h3>
                            <p><strong>Name:</strong> {{ e($order->prescription->patient->full_name ?? 'N/A') }}</p>
                            @if($order->prescription->patient && $order->prescription->patient->date_of_birth)
                            <p><strong>DOB:</strong> {{ $order->prescription->patient->date_of_birth->format('F d, Y') }}</p>
                            @endif
                            @if($order->prescription->patient && $order->prescription->patient->patient_number)
                            <p><strong>Patient #:</strong> {{ e($order->prescription->patient->patient_number) }}</p>
                            @endif
                        </div>
                    </td>
                    <td class="details-col">
                        <div class="info-box">
                            <h3>Physician Information</h3>
                            <p><strong>Name:</strong>Dr. {{ e($order->prescription->physician->physician->practice_name ?? 'N/A') }}</p>
                            <p><strong>Area of Speciality:</strong> {{ e($order->prescription->physician->physician->specialization) }}</p>
                            <p><strong>License:</strong> {{ e($order->prescription->physician->physician->license_number) }}</p>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        @endif

        @if($order->external_order_id)
            <div class="details-section">
                <table>
                    <tr>
                        <td class="details-col">
                            <div class="info-box">
                                <h3>Recepient Information</h3>
                                <p><strong> Name : </strong> {{$order->externalOrder->recipient_name}}</p>
                                <p><strong> Phone Number : </strong> {{$order->externalOrder->recipient_phone}}</p>
                                <p><strong> Address : </strong> {{$order->externalOrder->delivery_address}} , {{$order->externalOrder->delivery_city}}</p>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        @endif

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
                        <strong>{{ e($item->medicine->brand_name ?? 'N/A') }}</strong>
                        <br><small style="color: #666;"> {{ e($item->medicine->generic_name) }}</small>
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
            <p>This is a computer-generated document. No signature is required.</p>
            <p>&copy; {{ date('Y') }} {{ $company_info['company_name'] ?? 'PharmaConnect' }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>