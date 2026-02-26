<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Insurance Claim Form - {{ $claim->claim_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.4;
            color: #000;
            background: #fff;
        }

        .page {
            padding: 20px 30px;
            max-width: 210mm;
            margin: 0 auto;
        }

        /* Header Section */
        .header {
            border-bottom: 3px solid {{ $branding['primary_color'] }};
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .header-top {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }

        .header-left {
            display: table-cell;
            vertical-align: middle;
            width: 50%;
        }

        .header-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 50%;
        }

        .logo {
            max-width: 120px;
            max-height: 60px;
            margin-bottom: 5px;
        }

        .company-name {
            font-size: 18pt;
            font-weight: bold;
            color: {{ $branding['primary_color'] }};
            margin-bottom: 3px;
        }

        .document-title {
            font-size: 14pt;
            font-weight: bold;
            color: #000;
            margin-top: 5px;
        }

        .claim-number-box {
            border: 2px solid {{ $branding['primary_color'] }};
            background: #f8f9fa;
            padding: 8px 15px;
            display: inline-block;
            margin-bottom: 5px;
        }

        .claim-number-label {
            font-size: 8pt;
            color: #666;
            text-transform: uppercase;
        }

        .claim-number-value {
            font-size: 12pt;
            font-weight: bold;
            color: {{ $branding['primary_color'] }};
            font-family: 'Courier New', monospace;
        }

        /* Form Instructions */
        .instructions {
            background: #f8f9fa;
            border-left: 4px solid {{ $branding['primary_color'] }};
            padding: 10px 12px;
            margin-bottom: 15px;
            font-size: 8pt;
            line-height: 1.5;
        }

        .instructions-title {
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .instructions ul {
            margin-left: 15px;
            margin-top: 5px;
        }

        .instructions li {
            margin-bottom: 3px;
        }

        /* Section Headers */
        .section-header {
            background: {{ $branding['primary_color'] }};
            color: white;
            padding: 8px 12px;
            font-weight: bold;
            font-size: 10pt;
            text-transform: uppercase;
            margin-top: 15px;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }

        /* Form Fields */
        .form-section {
            margin-bottom: 15px;
        }

        .form-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }

        .form-group {
            display: table-cell;
            padding-right: 15px;
            vertical-align: top;
        }

        .form-group.full-width {
            display: block;
            width: 100%;
        }

        .form-group.half-width {
            width: 50%;
        }

        .form-group.third-width {
            width: 33.33%;
        }

        .form-label {
            font-size: 8pt;
            color: #333;
            font-weight: 600;
            margin-bottom: 3px;
            display: block;
        }

        .form-value {
            border-bottom: 1px solid #000;
            padding: 4px 0 2px 0;
            min-height: 20px;
            font-size: 9pt;
            color: #000;
        }

        .form-value.bold {
            font-weight: bold;
        }

        .checkbox-group {
            display: inline-block;
            margin-right: 20px;
        }

        .checkbox {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 1.5px solid #000;
            margin-right: 5px;
            vertical-align: middle;
            position: relative;
        }

        .checkbox.checked::after {
            content: '✓';
            position: absolute;
            top: -3px;
            left: 2px;
            font-size: 14pt;
            font-weight: bold;
        }

        /* Table Styles */
        .claim-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 8.5pt;
        }

        .claim-table th {
            background: #e9ecef;
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 8pt;
        }

        .claim-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: top;
        }

        .claim-table .medicine-name {
            font-weight: 600;
        }

        .claim-table .medicine-details {
            font-size: 7.5pt;
            color: #555;
            margin-top: 2px;
        }

        /* Amount Summary Box */
        .amount-summary {
            border: 2px solid #000;
            padding: 12px;
            margin: 15px 0;
            background: #f8f9fa;
        }

        .amount-row {
            display: table;
            width: 100%;
            margin-bottom: 6px;
        }

        .amount-label {
            display: table-cell;
            font-weight: 600;
            width: 70%;
            font-size: 9pt;
        }

        .amount-value {
            display: table-cell;
            text-align: right;
            font-weight: bold;
            font-size: 10pt;
            font-family: 'Courier New', monospace;
        }

        .amount-total {
            border-top: 2px solid #000;
            padding-top: 6px;
            margin-top: 6px;
        }

        .amount-total .amount-label {
            font-size: 10pt;
            font-weight: bold;
        }

        .amount-total .amount-value {
            font-size: 12pt;
            color: {{ $branding['primary_color'] }};
        }

        /* Declaration Box */
        .declaration-box {
            border: 2px solid #000;
            padding: 12px;
            margin: 15px 0;
            background: #fffef8;
        }

        .declaration-title {
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .declaration-text {
            font-size: 8pt;
            line-height: 1.5;
            margin-bottom: 6px;
        }

        /* Signature Section */
        .signature-section {
            display: table;
            width: 100%;
            margin-top: 20px;
        }

        .signature-block {
            display: table-cell;
            width: 48%;
            padding: 10px;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            margin: 20px 0 5px 0;
            min-height: 40px;
        }

        .signature-label {
            font-size: 8pt;
            color: #333;
            font-weight: 600;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid {{ $branding['primary_color'] }};
            font-size: 7.5pt;
            text-align: center;
            color: #666;
            line-height: 1.6;
        }

        .footer-bold {
            font-weight: bold;
            color: #000;
            margin-bottom: 5px;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 8pt;
            text-transform: uppercase;
        }

        .status-submitted { background: #3b82f6; color: white; }
        .status-under_review { background: #f59e0b; color: white; }
        .status-approved { background: #10b981; color: white; }
        .status-rejected { background: #ef4444; color: white; }
        .status-paid { background: #6366f1; color: white; }

        /* Helper Classes */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .small-text { font-size: 7.5pt; }
    </style>
</head>
<body>
    <div class="page">
        {{-- Header --}}
        <div class="header">
            <div class="header-top">
                <div class="header-left">
                    @if($branding['logo_url'])
                        <img src="{{ $branding['logo_url'] }}" alt="Logo" class="logo">
                    @endif
                    <div class="company-name">{{ $claim->insuranceProvider->company_name }}</div>
                </div>
                <div class="header-right">
                    <div class="claim-number-box">
                        <div class="claim-number-label">Claim Number</div>
                        <div class="claim-number-value">{{ $claim->claim_number }}</div>
                    </div>
                    <div style="margin-top: 5px;">
                        <span class="status-badge status-{{ str_replace(' ', '_', strtolower($claim->status)) }}">
                            {{ ucwords(str_replace('_', ' ', $claim->status)) }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="document-title">INSURANCE CLAIM FORM</div>
        </div>

        {{-- Instructions --}}
        <div class="instructions">
            <div class="instructions-title">Please help us to help you by:</div>
            <ul>
                <li>Completing all relevant questions in full as this can avoid the need for further correspondence with you and/or your physician and can delay us settling your claim</li>
                <li>Printing clearly or typing using BLOCK LETTERS</li>
                <li>Enclosing original receipts and prescription forms</li>
            </ul>
            <div style="margin-top: 8px; font-weight: bold; color: {{ $branding['primary_color'] }};">
                INSURANCE FRAUD IS A CRIME – PLEASE ENSURE ALL INFORMATION IS CORRECT
            </div>
        </div>

        {{-- Patient / Recipient Details Section --}}
        @if($claim->patient)
        {{-- Prescription-based claim: real patient record --}}
        <div class="section-header">1. Patient Details</div>
        <div class="form-section">
            <div class="form-row">
                <div class="form-group half-width">
                    <div class="form-label">Full Name</div>
                    <div class="form-value bold">{{ $claim->patient->first_name }} {{ $claim->patient->last_name }}</div>
                </div>
                <div class="form-group" style="width: 25%;">
                    <div class="form-label">Patient Number</div>
                    <div class="form-value">{{ $claim->patient->patient_number }}</div>
                </div>
                <div class="form-group" style="width: 25%;">
                    <div class="form-label">Date of Birth</div>
                    <div class="form-value">{{ $claim->patient->date_of_birth?->format('d/m/Y') ?? 'N/A' }}</div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half-width">
                    <div class="form-label">Policy Number</div>
                    <div class="form-value bold">{{ $claim->policy_number }}</div>
                </div>
                <div class="form-group half-width">
                    <div class="form-label">Contact Phone</div>
                    <div class="form-value">{{ $claim->patient->phone ?? 'N/A' }}</div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group full-width">
                    <div class="form-label">Physical Address</div>
                    <div class="form-value">{{ $claim->patient->address ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
        @else
        {{-- External order claim: recipient details from the external order --}}
        @php $externalOrder = $claim->externalOrder; @endphp
        <div class="section-header">1. Recipient Details</div>
        <div class="form-section">
            <div class="form-row">
                <div class="form-group half-width">
                    <div class="form-label">Recipient Name</div>
                    <div class="form-value bold">{{ $externalOrder?->recipient_name ?? 'N/A' }}</div>
                </div>
                <div class="form-group half-width">
                    <div class="form-label">Reference / Order Number</div>
                    <div class="form-value bold">{{ $claim->policy_number }}</div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half-width">
                    <div class="form-label">Contact Phone</div>
                    <div class="form-value">{{ $externalOrder?->recipient_phone ?? 'N/A' }}</div>
                </div>
                <div class="form-group half-width">
                    <div class="form-label">Contact Email</div>
                    <div class="form-value">{{ $externalOrder?->recipient_email ?? 'N/A' }}</div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group full-width">
                    <div class="form-label">Delivery Address</div>
                    <div class="form-value">
                        {{ $externalOrder?->delivery_address ?? 'N/A' }}
                        @if($externalOrder?->delivery_city), {{ $externalOrder->delivery_city }}@endif
                        @if($externalOrder?->delivery_county), {{ $externalOrder->delivery_county }}@endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Claim Details Section --}}
        <div class="section-header">2. Details of Claim</div>
        <div class="form-section">
            @if($claim->prescription)
            {{-- Prescription-based claim --}}
            <div class="form-row">
                <div class="form-group third-width">
                    <div class="form-label">Date of Illness</div>
                    <div class="form-value">{{ $claim->prescription->prescribed_at?->format('d/m/Y') ?? 'N/A' }}</div>
                </div>
                <div class="form-group third-width">
                    <div class="form-label">Time of First Illness Visit</div>
                    <div class="form-value">{{ $claim->prescription->prescribed_at?->format('H:i') ?? 'N/A' }}</div>
                </div>
                <div class="form-group third-width">
                    <div class="form-label">Submitted Date</div>
                    <div class="form-value">{{ $claim->submitted_at?->format('d/m/Y') ?? 'N/A' }}</div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group full-width">
                    <div class="form-label">Diagnosis / Condition</div>
                    <div class="form-value bold">{{ $claim->prescription->diagnosis ?? 'N/A' }}</div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half-width">
                    <div class="form-label">Prescribing Physician</div>
                    <div class="form-value">{{ $claim->prescription->physician->name ?? 'N/A' }}</div>
                </div>
                <div class="form-group half-width">
                    <div class="form-label">Prescription Number</div>
                    <div class="form-value">{{ $claim->prescription->prescription_number ?? 'N/A' }}</div>
                </div>
            </div>
            @else
            {{-- External order claim --}}
            @php $externalOrder = $externalOrder ?? $claim->externalOrder; @endphp
            <div class="form-row">
                <div class="form-group half-width">
                    <div class="form-label">Order Date</div>
                    <div class="form-value">{{ $externalOrder?->ordered_at?->format('d/m/Y') ?? 'N/A' }}</div>
                </div>
                <div class="form-group half-width">
                    <div class="form-label">Submitted Date</div>
                    <div class="form-value">{{ $claim->submitted_at?->format('d/m/Y') ?? 'N/A' }}</div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half-width">
                    <div class="form-label">External Order Number</div>
                    <div class="form-value bold">{{ $externalOrder?->order_number ?? 'N/A' }}</div>
                </div>
                <div class="form-group half-width">
                    <div class="form-label">Reference Number</div>
                    <div class="form-value">{{ $externalOrder?->reference_number ?? 'N/A' }}</div>
                </div>
            </div>

            @if($externalOrder?->notes)
            <div class="form-row">
                <div class="form-group full-width">
                    <div class="form-label">Order Notes</div>
                    <div class="form-value">{{ $externalOrder->notes }}</div>
                </div>
            </div>
            @endif
            @endif
        </div>

        {{-- Medication / Treatment Details --}}
        <div class="section-header">3. Claim Breakdown</div>
        <div class="form-section">
            <div class="form-label" style="margin-bottom: 5px;">Itemized list of medicines and costs:</div>
            <table class="claim-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 45%;">Description (Medicine Name, Strength, Form, etc.)</th>
                        <th style="width: 15%; text-align: center;">Quantity</th>
                        <th style="width: 20%; text-align: center;">Position (Dose, Frequency, etc.)</th>
                        <th style="width: 15%; text-align: right;">Amount (KES)</th>
                    </tr>
                </thead>
                <tbody>
                    @if($claim->prescription)
                        {{-- Prescription-based: items come from the prescription --}}
                        @forelse($claim->prescription->items as $index => $item)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>
                                <div class="medicine-name">{{ $item->medicine->generic_name }}</div>
                                <div class="medicine-details">
                                    @if($item->medicine->brand_name)
                                        Brand: {{ $item->medicine->brand_name }} •
                                    @endif
                                    {{ $item->medicine->strength }} • {{ $item->medicine->dosage_form }}
                                </div>
                            </td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="small-text">{{ $item->dosage_instructions ?? 'As prescribed' }}</td>
                            <td class="text-right bold">{{ number_format($item->total_price, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center" style="padding: 15px; color: #666;">
                                No prescription items recorded
                            </td>
                        </tr>
                        @endforelse
                    @else
                        {{-- External order: items come from the external order --}}
                        @php $externalOrder = $externalOrder ?? $claim->externalOrder; @endphp
                        @forelse($externalOrder?->items ?? [] as $index => $item)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>
                                <div class="medicine-name">{{ $item->medicine->generic_name ?? 'N/A' }}</div>
                                <div class="medicine-details">
                                    @if($item->medicine?->brand_name)
                                        Brand: {{ $item->medicine->brand_name }} •
                                    @endif
                                    {{ $item->medicine?->strength }} • {{ $item->medicine?->dosage_form }}
                                </div>
                            </td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="small-text">—</td>
                            <td class="text-right bold">{{ number_format($item->total_price, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center" style="padding: 15px; color: #666;">
                                No order items recorded
                            </td>
                        </tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
        </div>

        {{-- Amount Summary --}}
        <div class="section-header">4. Claim Amount Summary</div>
        <div class="amount-summary">
            <div class="amount-row">
                <div class="amount-label">Total Claimed Amount:</div>
                <div class="amount-value">KES {{ number_format($claim->claimed_amount, 2) }}</div>
            </div>
            <div class="amount-row">
                <div class="amount-label">Less: Deductible Amount:</div>
                <div class="amount-value">(KES {{ number_format($claim->deductible_amount ?? 0, 2) }})</div>
            </div>
            @if($claim->approved_amount)
            <div class="amount-row">
                <div class="amount-label">Approved Amount:</div>
                <div class="amount-value" style="color: #10b981;">KES {{ number_format($claim->approved_amount, 2) }}</div>
            </div>
            @endif
            <div class="amount-row amount-total">
                <div class="amount-label">NET PAYABLE AMOUNT:</div>
                <div class="amount-value">KES {{ number_format($claim->getNetAmountAttribute(), 2) }}</div>
            </div>
        </div>

        {{-- Additional Information --}}
        @if($claim->notes || ($claim->status === 'rejected' && $claim->rejection_reason))
        <div class="section-header">5. Additional Notes / Remarks</div>
        <div class="form-section">
            @if($claim->notes)
            <div class="form-group full-width">
                <div class="form-label">Claim Notes:</div>
                <div class="form-value">{{ $claim->notes }}</div>
            </div>
            @endif

            @if($claim->status === 'rejected' && $claim->rejection_reason)
            <div class="form-group full-width" style="margin-top: 10px;">
                <div class="form-label" style="color: #ef4444;">Rejection Reason:</div>
                <div class="form-value" style="color: #ef4444; font-weight: bold;">{{ $claim->rejection_reason }}</div>
            </div>
            @endif

            @if($claim->reviewed_at)
            <div class="form-group full-width" style="margin-top: 10px;">
                <div class="form-label">Review Date:</div>
                <div class="form-value">{{ $claim->reviewed_at->format('F d, Y \a\t H:i') }}</div>
            </div>
            @endif
        </div>
        @endif

        {{-- Declaration --}}
        <div class="declaration-box">
            <div class="declaration-title">Declaration</div>
            <div class="declaration-text">
                I declare that the information provided in this claim form is true and accurate to the best of my knowledge. 
                I understand that providing false or misleading information may result in the rejection of my claim and/or 
                legal action. I authorize {{ $claim->insuranceProvider->company_name }} to obtain any medical information 
                necessary to process this claim.
            </div>
        </div>

        {{-- Signature Section --}}
        <div class="signature-section">
            <div class="signature-block">
                <div class="signature-label">Claimant Signature:</div>
                <div class="signature-line"></div>
                <div class="signature-label">Date: {{ now()->format('d/m/Y') }}</div>
            </div>
            <div class="signature-block" style="border-left: 1px solid #ddd; padding-left: 20px;">
                <div class="signature-label">Insurance Company Officer:</div>
                <div class="signature-line"></div>
                <div class="signature-label">Date: _____________________</div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <div class="footer-bold">{{ $branding['footer_text'] }}</div>
            <div style="margin: 8px 0;">
                <strong>PRIVATE AND CONFIDENTIAL</strong> • This document contains sensitive medical and financial information
            </div>
            <div>
                Generated on {{ now()->format('F d, Y \a\t H:i') }} • 
                This is a computer-generated document and requires no signature for validity
            </div>
        </div>
    </div>
</body>
</html>