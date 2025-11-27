<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Insurance Statement</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .company-info {
            margin-bottom: 20px;
        }
        .statement-info {
            background-color: #f5f5f5;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th {
            background-color: #333;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 9pt;
        }
        table td {
            padding: 6px 8px;
            border-bottom: 1px solid #ddd;
            font-size: 9pt;
        }
        .summary-box {
            background-color: #e8f4f8;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .total-row {
            font-weight: bold;
            font-size: 12pt;
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 10px;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 8pt;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        .status-paid { background-color: #d1ecf1; color: #0c5460; }
        .status-submitted { background-color: #fff3cd; color: #856404; }
        .status-under_review { background-color: #cce5ff; color: #004085; }
    </style>
</head>
<body>
    <div class="header">
        <h1>INSURANCE CLAIMS STATEMENT</h1>
        <p><strong>PharmaConnect System</strong></p>
    </div>

    <div class="company-info">
        <h3>{{ $provider->company_name }}</h3>
        <p>
            Registration: {{ $provider->registration_number }}<br>
            Contact: {{ $provider->contact_person }}<br>
            Phone: {{ $provider->phone }}<br>
            Email: {{ $provider->email }}
        </p>
    </div>

    <div class="statement-info">
        <div class="summary-row">
            <span><strong>Statement Period:</strong></span>
            <span>{{ \Carbon\Carbon::parse($period['from'])->format('M d, Y') }} - {{ \Carbon\Carbon::parse($period['to'])->format('M d, Y') }}</span>
        </div>
        <div class="summary-row">
            <span><strong>Generated Date:</strong></span>
            <span>{{ now()->format('F d, Y H:i:s') }}</span>
        </div>
        <div class="summary-row">
            <span><strong>Total Claims:</strong></span>
            <span>{{ $summary['total_claims'] }}</span>
        </div>
    </div>

    <h3>Claims Summary</h3>
    <table>
        <thead>
            <tr>
                <th>Claim #</th>
                <th>Date</th>
                <th>Patient</th>
                <th>Policy #</th>
                <th>Claimed</th>
                <th>Approved</th>
                <th>Deductible</th>
                <th>Net Payable</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($claims as $claim)
            <tr>
                <td>{{ $claim->claim_number }}</td>
                <td>{{ $claim->submitted_at->format('M d, Y') }}</td>
                <td>{{ $claim->patient->full_name ?? 'N/A' }}</td>
                <td>{{ $claim->policy_number }}</td>
                <td>KES {{ number_format($claim->claimed_amount, 2) }}</td>
                <td>KES {{ number_format($claim->approved_amount ?? 0, 2) }}</td>
                <td>KES {{ number_format($claim->deductible_amount, 2) }}</td>
                <td>KES {{ number_format($claim->net_amount, 2) }}</td>
                <td>
                    <span class="status-badge status-{{ $claim->status }}">
                        {{ ucwords(str_replace('_', ' ', $claim->status)) }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-box">
        <h3>Financial Summary</h3>
        <div class="summary-row">
            <span>Total Claims:</span>
            <span>{{ $summary['total_claims'] }}</span>
        </div>
        <div class="summary-row">
            <span>Approved Claims:</span>
            <span>{{ $summary['approved_claims'] }}</span>
        </div>
        <div class="summary-row">
            <span>Rejected Claims:</span>
            <span>{{ $summary['rejected_claims'] }}</span>
        </div>
        <div class="summary-row">
            <span>Paid Claims:</span>
            <span>{{ $summary['paid_claims'] }}</span>
        </div>
        <hr style="margin: 15px 0;">
        <div class="summary-row">
            <span>Total Claimed Amount:</span>
            <span><strong>KES {{ number_format($summary['total_claimed'], 2) }}</strong></span>
        </div>
        <div class="summary-row">
            <span>Total Approved Amount:</span>
            <span><strong>KES {{ number_format($summary['total_approved'], 2) }}</strong></span>
        </div>
        <div class="summary-row">
            <span>Total Deductible:</span>
            <span><strong>KES {{ number_format($summary['total_deductible'], 2) }}</strong></span>
        </div>
        <div class="summary-row total-row">
            <span>Total Net Payable:</span>
            <span>KES {{ number_format($summary['total_payable'], 2) }}</span>
        </div>
    </div>

    <div class="footer">
        <p>This is a computer-generated statement from PharmaConnect System</p>
        <p>For queries, please contact operations@pharmaconnect.co.ke</p>
    </div>
</body>
</html>