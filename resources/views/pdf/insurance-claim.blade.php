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
            font-size: 11px;
            line-height: 1.5;
            color: #333;
        }

        .header {
            border-bottom: 3px solid {{ $branding['primary_color'] }};
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .logo {
            max-width: 200px;
            max-height: 80px;
            margin-bottom: 10px;
        }

        .header-text {
            font-size: 18px;
            font-weight: bold;
            color: {{ $branding['primary_color'] }};
            margin-bottom: 5px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: {{ $branding['primary_color'] }};
            margin-top: 20px;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid {{ $branding['secondary_color'] }};
        }

        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .info-row {
            display: table-row;
        }

        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 35%;
            padding: 5px 10px 5px 0;
            color: {{ $branding['secondary_color'] }};
        }

        .info-value {
            display: table-cell;
            padding: 5px 0;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .table th {
            background-color: {{ $branding['primary_color'] }};
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }

        .table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }

        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .amount-box {
            background-color: #f0f0f0;
            border: 2px solid {{ $branding['primary_color'] }};
            padding: 15px;
            margin: 20px 0;
        }

        .amount-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }

        .amount-label {
            font-weight: bold;
            color: {{ $branding['secondary_color'] }};
        }

        .amount-value {
            font-size: 14px;
            font-weight: bold;
        }

        .total-row {
            font-size: 16px;
            border-top: 2px solid {{ $branding['primary_color'] }};
            padding-top: 10px;
            margin-top: 10px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 2px solid {{ $branding['secondary_color'] }};
            padding: 10px;
            font-size: 9px;
            color: {{ $branding['secondary_color'] }};
            text-align: center;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 15px;
            font-weight: bold;
            font-size: 12px;
        }

        .status-submitted { background-color: #3b82f6; color: white; }
        .status-under_review { background-color: #f59e0b; color: white; }
        .status-approved { background-color: #10b981; color: white; }
        .status-rejected { background-color: #ef4444; color: white; }
        .status-paid { background-color: #6366f1; color: white; }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        @if($branding['logo_url'])
            <img src="{{ $branding['logo_url'] }}" alt="Logo" class="logo">
        @endif
        <div class="header-text">{{ $branding['header_text'] }}</div>
        <div>Date: {{ now()->format('F d, Y') }}</div>
    </div>

    {{-- Claim Information --}}
    <div class="section-title">CLAIM INFORMATION</div>
    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Claim Number:</div>
            <div class="info-value"><strong>{{ $claim->claim_number }}</strong></div>
        </div>
        <div class="info-row">
            <div class="info-label">Status:</div>
            <div class="info-value">
                <span class="status-badge status-{{ str_replace(' ', '_', strtolower($claim->status)) }}">
                    {{ ucwords(str_replace('_', ' ', $claim->status)) }}
                </span>
            </div>
        </div>
        <div class="info-row">
            <div class="info-label">Submission Date:</div>
            <div class="info-value">{{ $claim->submitted_at?->format('F d, Y') }}</div>
        </div>
        @if($claim->reviewed_at)
        <div class="info-row">
            <div class="info-label">Review Date:</div>
            <div class="info-value">{{ $claim->reviewed_at->format('F d, Y') }}</div>
        </div>
        @endif
    </div>

    {{-- Patient Information --}}
    <div class="section-title">PATIENT INFORMATION</div>
    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Patient Name:</div>
            <div class="info-value">{{ $claim->patient->first_name }} {{ $claim->patient->last_name }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Patient Number:</div>
            <div class="info-value">{{ $claim->patient->patient_number }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Policy Number:</div>
            <div class="info-value"><strong>{{ $claim->policy_number }}</strong></div>
        </div>
        <div class="info-row">
            <div class="info-label">Date of Birth:</div>
            <div class="info-value">{{ $claim->patient->date_of_birth?->format('F d, Y') }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Phone:</div>
            <div class="info-value">{{ $claim->patient->phone }}</div>
        </div>
    </div>

    {{-- Prescription Information --}}
    <div class="section-title">PRESCRIPTION DETAILS</div>
    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Prescription Number:</div>
            <div class="info-value">{{ $claim->prescription->prescription_number }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Physician:</div>
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

    {{-- Prescribed Medicines --}}
    <div class="section-title">PRESCRIBED MEDICINES</div>
    <table class="table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 40%;">Medicine</th>
                <th style="width: 15%;">Quantity</th>
                <th style="width: 20%;">Dosage Instructions</th>
                <th style="width: 20%; text-align: right;">Amount (KES)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($claim->prescription->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $item->medicine->generic_name }}</strong>
                    @if($item->medicine->brand_name)
                        <br><small>({{ $item->medicine->brand_name }})</small>
                    @endif
                    <br><small>{{ $item->medicine->strength }} - {{ $item->medicine->dosage_form }}</small>
                </td>
                <td>{{ $item->quantity }}</td>
                <td><small>{{ $item->dosage_instructions }}</small></td>
                <td style="text-align: right;">{{ number_format($item->total_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Order Details --}}
    @if($claim->prescription->orders->isNotEmpty())
    <div class="section-title">ORDER DETAILS</div>
    @foreach($claim->prescription->orders as $order)
    <div style="margin-bottom: 15px;">
        <div style="background-color: #f5f5f5; padding: 8px; margin-bottom: 5px;">
            <strong>Order: {{ $order->order_number }}</strong> | 
            Supplier: {{ $order->supplier->name ?? 'N/A' }} | 
            Status: {{ ucfirst($order->status) }}
        </div>
        <table class="table" style="margin-top: 5px;">
            <thead>
                <tr>
                    <th>Medicine</th>
                    <th style="text-align: center;">Quantity</th>
                    <th style="text-align: right;">Unit Price</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->medicine->generic_name ?? 'N/A' }}</td>
                    <td style="text-align: center;">{{ $item->quantity }}</td>
                    <td style="text-align: right;">{{ number_format($item->unit_price, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($item->total_price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach
    @endif

    {{-- Claim Amount Summary --}}
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
            <div class="amount-value" style="color: {{ $branding['primary_color'] }};">
                KES {{ number_format($claim->getNetAmountAttribute(), 2) }}
            </div>
        </div>
    </div>

    {{-- Notes --}}
    @if($claim->notes)
    <div class="section-title">NOTES</div>
    <div style="padding: 10px; background-color: #f9f9f9; border-left: 3px solid {{ $branding['secondary_color'] }};">
        {{ $claim->notes }}
    </div>
    @endif

    {{-- Rejection Reason --}}
    @if($claim->status === 'rejected' && $claim->rejection_reason)
    <div class="section-title">REJECTION REASON</div>
    <div style="padding: 10px; background-color: #fef2f2; border-left: 3px solid #ef4444; color: #991b1b;">
        {{ $claim->rejection_reason }}
    </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        {{ $branding['footer_text'] }}
        <br>
        <small>This is a computer-generated document. Generated on {{ now()->format('F d, Y \a\t H:i') }}</small>
    </div>
</body>
</html>