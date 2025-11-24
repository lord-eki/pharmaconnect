<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Insurance Claim - {{ $claim->claim_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.6;
            color: #1a1a1a;
            background: #ffffff;
        }

        .page-container {
            padding: 15px 25px;
            max-width: 100%;
        }

        /* Header Styling */
        .header {
            background: linear-gradient(135deg, {{ $branding['primary_color'] }} 0%, {{ $branding['secondary_color'] }} 100%);
            padding: 25px 30px;
            margin: -15px -25px 25px -25px;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }

        .header-content {
            position: relative;
            z-index: 1;
            display: table;
            width: 100%;
        }

        .header-left {
            display: table-cell;
            vertical-align: middle;
            width: 70%;
        }

        .header-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 30%;
        }

        .logo {
            max-width: 180px;
            max-height: 70px;
            margin-bottom: 12px;
            filter: brightness(0) invert(1);
        }

        .header-text {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }

        .header-subtitle {
            font-size: 11px;
            opacity: 0.95;
            font-weight: normal;
        }

        .claim-badge {
            background: rgba(255, 255, 255, 0.95);
            color: {{ $branding['primary_color'] }};
            padding: 12px 20px;
            border-radius: 8px;
            display: inline-block;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .claim-badge-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: bold;
            opacity: 0.7;
            margin-bottom: 3px;
        }

        .claim-badge-number {
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        /* Section Headers */
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: {{ $branding['primary_color'] }};
            margin: 25px 0 12px 0;
            padding: 10px 15px;
            background: linear-gradient(90deg, {{ $branding['primary_color'] }}15 0%, transparent 100%);
            border-left: 4px solid {{ $branding['primary_color'] }};
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Info Cards */
        .info-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 15px;
        }

        .info-grid {
            display: table;
            width: 100%;
        }

        .info-row {
            display: table-row;
        }

        .info-label {
            display: table-cell;
            font-weight: 600;
            width: 38%;
            padding: 6px 15px 6px 0;
            color: {{ $branding['secondary_color'] }};
            font-size: 10px;
        }

        .info-value {
            display: table-cell;
            padding: 6px 0;
            color: #1a1a1a;
            font-size: 10px;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-submitted { 
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); 
            color: white; 
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
        }
        .status-under_review { 
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); 
            color: white;
            box-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);
        }
        .status-approved { 
            background: linear-gradient(135deg, #10b981 0%, #059669 100%); 
            color: white;
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
        }
        .status-rejected { 
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); 
            color: white;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
        }
        .status-paid { 
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); 
            color: white;
            box-shadow: 0 2px 4px rgba(99, 102, 241, 0.3);
        }

        /* Modern Table */
        .table-container {
            margin: 15px 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .table thead {
            background: linear-gradient(135deg, {{ $branding['primary_color'] }} 0%, {{ $branding['secondary_color'] }} 100%);
        }

        .table th {
            color: white;
            padding: 12px 10px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table td {
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 9px;
        }

        .table tbody tr {
            transition: background-color 0.2s;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Amount Summary Box */
        .amount-box {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border: 2px solid {{ $branding['primary_color'] }};
            border-radius: 10px;
            padding: 20px;
            margin: 25px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .amount-row {
            display: table;
            width: 100%;
            padding: 8px 0;
        }

        .amount-label {
            display: table-cell;
            font-weight: 600;
            color: {{ $branding['secondary_color'] }};
            font-size: 11px;
            width: 60%;
        }

        .amount-value {
            display: table-cell;
            font-size: 13px;
            font-weight: bold;
            text-align: right;
            width: 40%;
        }

        .total-row {
            border-top: 3px solid {{ $branding['primary_color'] }};
            padding-top: 12px;
            margin-top: 8px;
        }

        .total-row .amount-label {
            font-size: 13px;
            color: {{ $branding['primary_color'] }};
        }

        .total-row .amount-value {
            font-size: 18px;
            color: {{ $branding['primary_color'] }};
        }

        /* Alert Boxes */
        .alert-box {
            padding: 15px 18px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid;
        }

        .alert-info {
            background: #e0f2fe;
            border-left-color: #0284c7;
            color: #075985;
        }

        .alert-danger {
            background: #fee2e2;
            border-left-color: #dc2626;
            color: #991b1b;
        }

        /* Order Section */
        .order-header {
            background: linear-gradient(90deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 10px 15px;
            margin: 15px 0 8px 0;
            border-radius: 6px;
            border-left: 3px solid {{ $branding['secondary_color'] }};
            font-size: 10px;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(180deg, transparent 0%, #f8f9fa 30%);
            border-top: 2px solid {{ $branding['secondary_color'] }};
            padding: 12px 25px;
            font-size: 8px;
            color: {{ $branding['secondary_color'] }};
            text-align: center;
        }

        .footer-line {
            margin: 3px 0;
        }

        /* Decorative Elements */
        .divider {
            height: 2px;
            background: linear-gradient(90deg, {{ $branding['primary_color'] }} 0%, transparent 100%);
            margin: 15px 0;
        }

        /* Medicine Badge */
        .medicine-name {
            font-weight: bold;
            color: #1a1a1a;
        }

        .medicine-brand {
            color: #6b7280;
            font-size: 8px;
        }

        .medicine-strength {
            color: {{ $branding['secondary_color'] }};
            font-size: 8px;
            font-weight: 600;
        }

        /* Utility Classes */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .mt-10 { margin-top: 10px; }
    </style>
</head>
<body>
    <div class="page-container">
        {{-- Header --}}
        <div class="header">
            <div class="header-content">
                <div class="header-left">
                    @if($branding['logo_url'])
                        <img src="{{ $branding['logo_url'] }}" alt="Logo" class="logo">
                    @endif
                    <div class="header-text">{{ $branding['header_text'] }}</div>
                    <div class="header-subtitle">Generated on {{ now()->format('F d, Y \a\t H:i') }}</div>
                </div>
                <div class="header-right">
                    <div class="claim-badge">
                        <div class="claim-badge-label">Claim Number</div>
                        <div class="claim-badge-number">{{ $claim->claim_number }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Claim Status Overview --}}
        <div class="info-card">
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Current Status:</div>
                    <div class="info-value">
                        <span class="status-badge status-{{ str_replace(' ', '_', strtolower($claim->status)) }}">
                            {{ ucwords(str_replace('_', ' ', $claim->status)) }}
                        </span>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Submission Date:</div>
                    <div class="info-value">{{ $claim->submitted_at?->format('F d, Y') ?? 'Not submitted' }}</div>
                </div>
                @if($claim->reviewed_at)
                <div class="info-row">
                    <div class="info-label">Review Date:</div>
                    <div class="info-value">{{ $claim->reviewed_at->format('F d, Y') }}</div>
                </div>
                @endif
            </div>
        </div>

        {{-- Patient Information --}}
        <div class="section-title">Patient Information</div>
        <div class="info-card">
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Patient Name:</div>
                    <div class="info-value font-bold">{{ $claim->patient->first_name }} {{ $claim->patient->last_name }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Patient Number:</div>
                    <div class="info-value">{{ $claim->patient->patient_number }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Policy Number:</div>
                    <div class="info-value font-bold">{{ $claim->policy_number }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Date of Birth:</div>
                    <div class="info-value">{{ $claim->patient->date_of_birth?->format('F d, Y') }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Contact Phone:</div>
                    <div class="info-value">{{ $claim->patient->phone }}</div>
                </div>
            </div>
        </div>

        {{-- Prescription Details --}}
        <div class="section-title">Prescription Details</div>
        <div class="info-card">
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Prescription Number:</div>
                    <div class="info-value font-bold">{{ $claim->prescription->prescription_number }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Prescribing Physician:</div>
                    <div class="info-value">{{ $claim->prescription->physician->name }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Prescribed Date:</div>
                    <div class="info-value">{{ $claim->prescription->prescribed_at?->format('F d, Y') }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Diagnosis:</div>
                    <div class="info-value">{{ $claim->prescription->diagnosis }}</div>
                </div>
            </div>
        </div>

        {{-- Prescribed Medicines --}}
        <div class="section-title">Prescribed Medicines</div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 35%;">Medicine Details</th>
                        <th style="width: 12%;">Quantity</th>
                        <th style="width: 28%;">Dosage Instructions</th>
                        <th style="width: 20%; text-align: right;">Amount (KES)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($claim->prescription->items as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <div class="medicine-name">{{ $item->medicine->generic_name }}</div>
                            @if($item->medicine->brand_name)
                                <div class="medicine-brand">({{ $item->medicine->brand_name }})</div>
                            @endif
                            <div class="medicine-strength">{{ $item->medicine->strength }} • {{ $item->medicine->dosage_form }}</div>
                        </td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td><small>{{ $item->dosage_instructions }}</small></td>
                        <td class="text-right font-bold">{{ number_format($item->total_price, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Order Details --}}
        @if($claim->prescription->orders->isNotEmpty())
        <div class="section-title">Order Details</div>
        @foreach($claim->prescription->orders as $order)
        <div class="order-header">
            <strong>Order: {{ $order->order_number }}</strong> • 
            Supplier: {{ $order->supplier->name ?? 'N/A' }} • 
            Status: <strong>{{ ucfirst($order->status) }}</strong>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 45%;">Medicine</th>
                        <th style="width: 18%; text-align: center;">Quantity</th>
                        <th style="width: 18%; text-align: right;">Unit Price</th>
                        <th style="width: 19%; text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->medicine->generic_name ?? 'N/A' }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right font-bold">{{ number_format($item->total_price, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endforeach
        @endif

        {{-- Claim Amount Summary --}}
        <div class="section-title">Claim Amount Summary</div>
        <div class="amount-box">
            <div class="amount-row">
                <div class="amount-label">Total Claimed Amount:</div>
                <div class="amount-value">KES {{ number_format($claim->claimed_amount, 2) }}</div>
            </div>
            <div class="amount-row">
                <div class="amount-label">Deductible Amount:</div>
                <div class="amount-value">KES {{ number_format($claim->deductible_amount, 2) }}</div>
            </div>
            @if($claim->approved_amount)
            <div class="amount-row">
                <div class="amount-label">Approved Amount:</div>
                <div class="amount-value" style="color: #10b981;">KES {{ number_format($claim->approved_amount, 2) }}</div>
            </div>
            @endif
            <div class="amount-row total-row">
                <div class="amount-label">Net Claim Amount:</div>
                <div class="amount-value">KES {{ number_format($claim->getNetAmountAttribute(), 2) }}</div>
            </div>
        </div>

        {{-- Notes --}}
        @if($claim->notes)
        <div class="section-title">Additional Notes</div>
        <div class="alert-box alert-info">
            {{ $claim->notes }}
        </div>
        @endif

        {{-- Rejection Reason --}}
        @if($claim->status === 'rejected' && $claim->rejection_reason)
        <div class="section-title">Rejection Reason</div>
        <div class="alert-box alert-danger">
            <strong>Reason:</strong> {{ $claim->rejection_reason }}
        </div>
        @endif

        {{-- Footer --}}
        <div class="footer">
            <div class="footer-line">{{ $branding['footer_text'] }}</div>
            <div class="footer-line"><strong>PRIVATE AND CONFIDENTIAL</strong> • This document contains sensitive medical and financial information</div>
            <div class="footer-line">This is a computer-generated document and requires no signature for validity</div>
        </div>
    </div>
</body>
</html>