<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insurance Invoice - {{ $invoice->invoice_number }}</title>
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
            border-bottom: 3px solid #059669;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header-top table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .company-info {
            width: 50%;
            vertical-align: top;
        }
        
        .company-info h1 {
            color: #059669;
            font-size: 24pt;
            margin-bottom: 5px;
        }
        
        .company-info p {
            font-size: 10pt;
            color: #666;
            margin-bottom: 2px;
        }
        
        .invoice-title {
            width: 50%;
            text-align: right;
            vertical-align: top;
        }
        
        .invoice-title h2 {
            font-size: 32pt;
            color: #047857;
            font-weight: bold;
            margin: 0;
        }
        
        .invoice-title p {
            font-size: 11pt;
            color: #666;
            margin-top: 5px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 10pt;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .status-paid { background-color: #d1fae5; color: #065f46; }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-overdue { background-color: #fee2e2; color: #991b1b; }
        .status-cancelled { background-color: #e5e7eb; color: #374151; }
        
        .info-section {
            width: 100%;
            margin-bottom: 30px;
        }
        
        .info-section table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .info-col {
            width: 50%;
            padding: 5px;
            vertical-align: top;
        }
        
        .info-box {
            border: 1px solid #e5e7eb;
            padding: 15px;
            background-color: #f9fafb;
            border-radius: 5px;
            min-height: 160px;
        }
        
        .info-box h3 {
            font-size: 12pt;
            color: #059669;
            margin-bottom: 10px;
            border-bottom: 2px solid #d1fae5;
            padding-bottom: 5px;
        }
        
        .info-box p {
            margin-bottom: 6px;
            font-size: 10pt;
            line-height: 1.5;
        }
        
        .info-box strong {
            color: #111;
            font-weight: 600;
        }
        
        .highlight-box {
            background-color: #ecfdf5;
            border-left: 4px solid #059669;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
        }
        
        .items-table thead {
            background-color: #059669;
            color: white;
        }
        
        .items-table th {
            padding: 12px 10px;
            text-align: left;
            font-size: 10pt;
            font-weight: bold;
        }
        
        .items-table td {
            padding: 10px;
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
        
        .section-title {
            font-size: 13pt;
            color: #059669;
            margin-top: 25px;
            margin-bottom: 12px;
            font-weight: 600;
            border-bottom: 1px solid #d1fae5;
            padding-bottom: 5px;
        }
        
        .summary-section {
            margin-top: 30px;
            width: 100%;
        }
        
        .summary-wrapper table {
            width: 45%;
            margin-left: 55%;
            border-collapse: collapse;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
        }
        
        .summary-table td {
            padding: 10px 15px;
            font-size: 10pt;
        }
        
        .summary-table .label {
            text-align: right;
            font-weight: 600;
            color: #666;
            width: 60%;
        }
        
        .summary-table .amount {
            text-align: right;
            width: 40%;
            font-weight: 500;
        }
        
        .summary-table .total-row {
            border-top: 2px solid #059669;
            background-color: #ecfdf5;
        }
        
        .summary-table .total-row td {
            font-weight: bold;
            font-size: 12pt;
            color: #047857;
            padding-top: 12px;
        }
        
        .payment-info {
            margin-top: 30px;
            padding: 20px;
            background-color: #ecfdf5;
            border: 1px solid #d1fae5;
            border-radius: 5px;
        }
        
        .payment-info h4 {
            color: #047857;
            margin-bottom: 12px;
            font-size: 12pt;
        }
        
        .payment-info p {
            margin-bottom: 8px;
            font-size: 10pt;
            color: #065f46;
        }
        
        .notes-section {
            margin-top: 25px;
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
            line-height: 1.6;
        }
        
        .terms-section {
            margin-top: 30px;
            padding: 15px;
            background-color: #f3f4f6;
            border-radius: 5px;
            border: 1px solid #e5e7eb;
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
            margin-bottom: 6px;
            line-height: 1.4;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
        }
        
        .footer p {
            font-size: 9pt;
            color: #666;
            margin-bottom: 5px;
        }
        
        .footer .contact-info {
            margin-top: 15px;
            font-size: 9pt;
            color: #888;
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
                            <h1>Pharmaconnect</h1>
                            <p>P.O. Box 12345, Nairobi, Kenya</p>
                            <p>Tel: +254 700 000 000</p>
                            <p>Email: billing@pharmaconnect.com</p>
                            <p>Website: www.pharmaconnect.com</p>
                        </td>
                        <td class="invoice-title">
                            <h2>INVOICE</h2>
                            <p>{{ $invoice->invoice_number }}</p>
                            <span class="status-badge status-{{ $invoice->status }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Invoice Details and Billing Info -->
        <div class="info-section">
            <table>
                <tr>
                    <td class="info-col">
                        <div class="info-box">
                            <h3>Invoice Details</h3>
                            <p><strong>Invoice Number:</strong> {{ $invoice->invoice_number }}</p>
                            <p><strong>Invoice Date:</strong> {{ $invoice->created_at->format('F d, Y') }}</p>
                            <p><strong>Due Date:</strong> {{ $invoice->due_date ? $invoice->due_date->format('F d, Y') : 'Upon Receipt' }}</p>
                            <p><strong>Order Reference:</strong> {{ $invoice->order->order_number ?? 'N/A' }}</p>
                            @if($invoice->paid_at)
                            <p><strong>Payment Date:</strong> {{ $invoice->paid_at->format('F d, Y H:i') }}</p>
                            @endif
                        </div>
                    </td>
                    <td class="info-col">
                        <div class="info-box highlight-box">
                            <h3>Bill To: Insurance Provider</h3>
                            @php
                                $insurance = $invoice->order->prescription->patient->insuranceProvider ?? null;
                            @endphp
                            @if($insurance)
                            <p><strong>{{ e($insurance->company_name) }}</strong></p>
                            @if($insurance->contact_person)
                            <p>Attn: {{ e($insurance->contact_person) }}</p>
                            @endif
                            @if($insurance->registration_number)
                            <p>Reg. No: {{ e($insurance->registration_number) }}</p>
                            @endif
                            @if($insurance->email)
                            <p>Email: {{ e($insurance->email) }}</p>
                            @endif
                            @if($insurance->phone)
                            <p>Phone: {{ e($insurance->phone) }}</p>
                            @endif
                            @if($insurance->address)
                            <p>{{ e($insurance->address) }}</p>
                            @endif
                            @else
                            <p>No insurance information available</p>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Patient Information -->
        @if($invoice->order->prescription && $invoice->order->prescription->patient)
        <div class="info-section">
            <table>
                <tr>
                    <td class="info-col">
                        <div class="info-box">
                            <h3>Patient Information</h3>
                            @php
                                $patient = $invoice->order->prescription->patient;
                            @endphp
                            <p><strong>Name:</strong> {{ e($patient->full_name ?? 'N/A') }}</p>
                            @if($patient->patient_number)
                            <p><strong>Patient ID:</strong> {{ e($patient->patient_number) }}</p>
                            @endif
                            @if($patient->date_of_birth)
                            <p><strong>Date of Birth:</strong> {{ $patient->date_of_birth->format('F d, Y') }}</p>
                            @endif
                            @if($patient->insurance_policy_number)
                            <p><strong>Policy Number:</strong> {{ e($patient->insurance_policy_number) }}</p>
                            @endif
                            @if($patient->insurance_member_id)
                            <p><strong>Member ID:</strong> {{ e($patient->insurance_member_id) }}</p>
                            @endif
                        </div>
                    </td>
                    @if($invoice->order->prescription->physician)
                    <td class="info-col">
                        <div class="info-box">
                            <h3>Prescribing Physician</h3>
                            @php
                                $physician = $invoice->order->prescription->physician;
                            @endphp
                            <p><strong>Dr. {{ e($physician->full_name ?? 'N/A') }}</strong></p>
                            @if($physician->specialty)
                            <p>{{ e($physician->specialty) }}</p>
                            @endif
                            @if($physician->license_number)
                            <p>License: {{ e($physician->license_number) }}</p>
                            @endif
                            @if($physician->phone)
                            <p>Phone: {{ e($physician->phone) }}</p>
                            @endif
                            <p><strong>Prescription:</strong> {{ $invoice->order->prescription->prescription_number }}</p>
                        </div>
                    </td>
                    @endif
                </tr>
            </table>
        </div>
        @endif

        <!-- Invoice Items -->
        <h3 class="section-title">Invoice Items</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 35%;">Description</th>
                    <th style="width: 15%;">Medicine Code</th>
                    <th style="width: 10%;" class="text-center">Quantity</th>
                    <th style="width: 12%;" class="text-right">Unit Price</th>
                    <th style="width: 10%;" class="text-right">Tax</th>
                    <th style="width: 13%;" class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->order->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ e($item->medicine->name ?? 'N/A') }}</strong>
                        @if($item->medicine && $item->medicine->description)
                        <br><small style="color: #666;">{{ e($item->medicine->description) }}</small>
                        @endif
                    </td>
                    <td>
                        @if($item->medicine && $item->medicine->sku)
                        {{ e($item->medicine->sku) }}
                        @else
                        N/A
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($item->quantity, 0) }} {{ $item->unit ?? 'units' }}</td>
                    <td class="text-right">{{ $invoice->currency }} {{ number_format($item->unit_price ?? 0, 2) }}</td>
                    <td class="text-right">{{ $invoice->currency }} {{ number_format($item->tax_amount ?? 0, 2) }}</td>
                    <td class="text-right"><strong>{{ $invoice->currency }} {{ number_format($item->total_price ?? 0, 2) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Invoice Summary -->
        <div class="summary-section">
            <div class="summary-wrapper">
                <table class="summary-table">
                    <tr>
                        <td class="label">Subtotal:</td>
                        <td class="amount">{{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</td>
                    </tr>
                    @if($invoice->discount_amount > 0)
                    <tr>
                        <td class="label">Discount:</td>
                        <td class="amount" style="color: #dc2626;">- {{ $invoice->currency }} {{ number_format($invoice->discount_amount, 2) }}</td>
                    </tr>
                    @endif
                    @if($invoice->tax_amount > 0)
                    <tr>
                        <td class="label">Tax (16% VAT):</td>
                        <td class="amount">{{ $invoice->currency }} {{ number_format($invoice->tax_amount, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td class="label">Total Amount Due:</td>
                        <td class="amount">{{ $invoice->currency }} {{ number_format($invoice->total_amount, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Payment Information -->
        <div class="payment-info">
            <h4>Payment Information</h4>
            <p><strong>Bank Name:</strong> Kenya Commercial Bank</p>
            <p><strong>Account Name:</strong> Pharmaconnect Limited</p>
            <p><strong>Account Number:</strong> 1234567890</p>
            <p><strong>Bank Code:</strong> 01-XXX</p>
            <p><strong>SWIFT Code:</strong> KCBLKENXXXX</p>
            <p style="margin-top: 12px; font-weight: 600;">Please include invoice number {{ $invoice->invoice_number }} in payment reference.</p>
        </div>

        <!-- Notes -->
        @if($invoice->notes)
        <div class="notes-section">
            <h4>Additional Notes</h4>
            <p>{{ e($invoice->notes) }}</p>
        </div>
        @endif

        <!-- Terms and Conditions -->
        <div class="terms-section">
            <h4>Terms and Conditions</h4>
            <ul>
                <li>Payment is due within 30 days from the invoice date unless otherwise specified</li>
                <li>All medications were dispensed as per valid prescription</li>
                <li>This invoice is subject to verification of insurance coverage</li>
                <li>Late payments may be subject to additional charges as per agreement</li>
                <li>Please verify all details and contact us immediately if any discrepancies are found</li>
                <li>All prescribed medications were dispensed by licensed pharmacists</li>
            </ul>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Thank you for your business!</p>
            <p style="font-weight: 600; margin-top: 10px;">This is a computer-generated invoice and is valid without signature.</p>
            <p>Generated on {{ now()->format('F d, Y \a\t H:i') }}</p>
            <div class="contact-info">
                <p>For queries regarding this invoice, please contact our billing department</p>
                <p>Email: billing@pharmaconnect.com | Tel: +254 700 000 000</p>
            </div>
        </div>
    </div>
</body>
</html>