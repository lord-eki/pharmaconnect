<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Monthly Insurance Statement</title>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
}

html,body{
height:100%;
}

body{
font-family:'DejaVu Sans',sans-serif;
font-size:11px;
color:#1a1a2e;
background:#fff;
}

/* Prevent footer overlap */
.page-content{
padding-bottom:90px;
}

/* ── HEADER ── */

.header{
background:#1a1a2e;
color:#fff;
padding:28px 36px 22px;
}

.header-row{
display:table;
width:100%;
}

.header-left{
display:table-cell;
vertical-align:middle;
}

.header-right{
display:table-cell;
vertical-align:middle;
text-align:right;
}

.brand{
font-size:22px;
font-weight:700;
color:#f97316;
letter-spacing:-0.5px;
}

.brand-sub{
font-size:9.5px;
color:#94a3b8;
margin-top:3px;
}

.doc-title{
font-size:19px;
font-weight:700;
color:#fff;
letter-spacing:0.3px;
}

.doc-period{
font-size:12px;
color:#f97316;
font-weight:600;
margin-top:4px;
}

.doc-meta{
font-size:9px;
color:#94a3b8;
margin-top:2px;
}

.accent-bar{
height:4px;
background:linear-gradient(to right,#f97316,#fdba74);
}

/* ── PROVIDER BAND ── */

.provider-band{
background:#f8fafc;
padding:18px 36px;
border-bottom:1px solid #e2e8f0;
}

.provider-row{
display:table;
width:100%;
}

.provider-left{
display:table-cell;
vertical-align:top;
width:55%;
}

.provider-right{
display:table-cell;
vertical-align:top;
text-align:right;
}

.label{
font-size:8.5px;
font-weight:700;
text-transform:uppercase;
letter-spacing:1px;
color:#f97316;
margin-bottom:5px;
}

.provider-name{
font-size:15px;
font-weight:700;
color:#1a1a2e;
}

.provider-detail{
font-size:10px;
color:#64748b;
margin-top:2px;
}

/* ── SUMMARY CARDS ── */

.summary-section{
padding:20px 36px 0;
}

.summary-row{
display:table;
width:100%;
border-spacing:10px;
}

.summary-card{
display:table-cell;
width:20%;
background:#fff;
border:1px solid #e2e8f0;
border-top:3px solid #e2e8f0;
border-radius:6px;
padding:12px 14px;
}

.summary-card.orange { border-top-color:#f97316; }
.summary-card.green  { border-top-color:#22c55e; }
.summary-card.amber  { border-top-color:#f59e0b; }
.summary-card.blue   { border-top-color:#3b82f6; }
.summary-card.red    { border-top-color:#ef4444; }

.card-label{
font-size:9px;
color:#64748b;
text-transform:uppercase;
letter-spacing:0.8px;
}

.card-value{
font-size:13px;
font-weight:700;
margin-top:4px;
}

.card-value.orange { color:#f97316; }
.card-value.green  { color:#16a34a; }
.card-value.amber  { color:#d97706; }
.card-value.blue   { color:#2563eb; }
.card-value.red    { color:#dc2626; }

.card-sub{
font-size:9px;
color:#94a3b8;
margin-top:2px;
}

/* ── STATUS BREAKDOWN ── */

.breakdown-section{
padding:20px 36px 0;
}

.breakdown-row{
display:table;
width:100%;
border-spacing:8px;
}

.breakdown-cell{
display:table-cell;
width:25%;
}

.breakdown-item{
background:#f8fafc;
border-left:3px solid #e2e8f0;
border-radius:4px;
padding:10px 12px;
}

.breakdown-item.submitted { border-left-color:#94a3b8; }
.breakdown-item.review    { border-left-color:#3b82f6; }
.breakdown-item.approved  { border-left-color:#22c55e; }
.breakdown-item.rejected  { border-left-color:#ef4444; }
.breakdown-item.paid      { border-left-color:#f97316; }

.breakdown-label{
font-size:9px;
text-transform:uppercase;
letter-spacing:0.8px;
color:#64748b;
}

.breakdown-count{
font-size:18px;
font-weight:700;
color:#1a1a2e;
margin-top:2px;
}

.breakdown-amount{
font-size:9px;
color:#64748b;
margin-top:1px;
}

/* ── SECTIONS ── */

.section{
padding:20px 36px 0;
}

.section-heading{
font-size:10px;
font-weight:700;
text-transform:uppercase;
letter-spacing:1px;
color:#1a1a2e;
border-bottom:2px solid #f97316;
padding-bottom:6px;
margin-bottom:12px;
}

/* ── CLAIMS TABLE ── */

.claims-table{
width:100%;
border-collapse:collapse;
font-size:9.5px;
}

.claims-table th{
background:#1a1a2e;
color:#fff;
padding:7px 8px;
text-align:left;
font-size:8.5px;
text-transform:uppercase;
}

.claims-table td{
padding:6px 8px;
border-bottom:1px solid #f1f5f9;
vertical-align:middle;
}

.claims-table tr:nth-child(even) td{
background:#f8fafc;
}

.claims-table td.right,
.claims-table th.right{
text-align:right;
}

.claims-table .total-row td{
background:#fef3e8;
font-weight:700;
border-top:2px solid #f97316;
}

/* ── BADGES ── */

.badge{
display:inline-block;
padding:2px 7px;
border-radius:9px;
font-size:8px;
font-weight:700;
text-transform:uppercase;
}

.badge-submitted   { background:#f1f5f9; color:#475569; }
.badge-under_review{ background:#dbeafe; color:#1d4ed8; }
.badge-approved    { background:#dcfce7; color:#15803d; }
.badge-rejected    { background:#fee2e2; color:#b91c1c; }
.badge-paid        { background:#fef3c7; color:#b45309; }

.amount-positive { color:#16a34a; font-weight:700; }
.amount-muted    { color:#64748b; }

/* ── TOTALS SUMMARY BAR ── */

.totals-bar{
background:#1a1a2e;
color:#fff;
padding:14px 36px;
margin:20px 36px 0;
border-radius:6px;
display:table;
width:calc(100% - 72px);
}

.totals-bar-cell{
display:table-cell;
width:25%;
text-align:center;
}

.totals-bar-cell + .totals-bar-cell{
border-left:1px solid #334155;
}

.totals-bar-label{
font-size:8.5px;
color:#94a3b8;
text-transform:uppercase;
letter-spacing:0.8px;
}

.totals-bar-value{
font-size:13px;
font-weight:700;
margin-top:3px;
}

.totals-bar-value.orange { color:#f97316; }
.totals-bar-value.green  { color:#4ade80; }
.totals-bar-value.red    { color:#f87171; }
.totals-bar-value.white  { color:#fff; }

/* ── FOOTER ── */

.footer{
position:fixed;
bottom:0;
left:0;
right:0;
background:#1a1a2e;
padding:14px 36px;
color:#94a3b8;
font-size:9px;
text-align:center;
}

.footer strong{
color:#f97316;
}

.footer:after{
content:" | Page " counter(page) " of " counter(pages);
}

/* ── UTILITIES ── */

.mt-16 { margin-top:16px; }
.mt-20 { margin-top:20px; }
.pb-36 { padding-bottom:36px; }
.text-muted { color:#64748b; }

</style>
</head>

<body>

<div class="page-content">

    {{-- ── HEADER ── --}}
    <div class="header">
        <div class="header-row">

            <div class="header-left">
                <div class="brand">{{ $provider->company_name ?? 'Insurance Provider' }}</div>
                @if($provider->address ?? null)
                    <div class="brand-sub">{{ $provider->address }}</div>
                @endif
                <div class="brand-sub">
                    @if($provider->phone ?? null) Tel: {{ $provider->phone }} @endif
                    @if($provider->email ?? null) | {{ $provider->email }} @endif
                </div>
            </div>

            <div class="header-right">
                <div class="doc-title">MONTHLY STATEMENT</div>
                <div class="doc-period">{{ $month }}</div>
                <div class="doc-meta">Generated: {{ now()->format('F d, Y \a\t H:i') }}</div>
            </div>

        </div>
    </div>

    <div class="accent-bar"></div>

    <div class="provider-band">
        <div class="provider-row">

            <div class="provider-left">
                <div class="label">Issued By</div>
                <div class="provider-name">{{ $provider->company_name ?? '—' }}</div>
                @if($provider->license_number ?? null)
                    <div class="provider-detail">License No: {{ $provider->license_number }}</div>
                @endif
                @if($provider->email ?? null)
                    <div class="provider-detail">{{ $provider->email }}</div>
                @endif
                @if($provider->phone ?? null)
                    <div class="provider-detail">{{ $provider->phone }}</div>
                @endif
            </div>

            <div class="provider-right">
                <div class="label">Statement Period</div>
                <div class="provider-name" style="font-size:13px;">{{ $month }}</div>
                <div class="provider-detail">Total Claims: {{ $summary['total_claims'] }}</div>
            </div>

        </div>
    </div>

    {{-- ── SUMMARY CARDS ── --}}
    <div class="summary-section">
        <div class="summary-row">

            <div class="summary-card orange">
                <div class="card-label">Total Claimed</div>
                <div class="card-value orange">KES {{ number_format($summary['total_claimed'], 2) }}</div>
                <div class="card-sub">{{ $summary['total_claims'] }} claims</div>
            </div>

            <div class="summary-card green">
                <div class="card-label">Total Approved</div>
                <div class="card-value green">KES {{ number_format($summary['total_approved'], 2) }}</div>
                <div class="card-sub">{{ $summary['approved_claims'] }} claims</div>
            </div>

            <div class="summary-card blue">
                <div class="card-label">Total Payable</div>
                <div class="card-value blue">KES {{ number_format($summary['total_payable'], 2) }}</div>
                <div class="card-sub">Net after deductibles</div>
            </div>

            <div class="summary-card amber">
                <div class="card-label">Total Deductible</div>
                <div class="card-value amber">KES {{ number_format($summary['total_deductible'], 2) }}</div>
                <div class="card-sub">Member co-pays</div>
            </div>

            <div class="summary-card red">
                <div class="card-label">Rejected</div>
                <div class="card-value red">{{ $summary['rejected_claims'] }}</div>
                <div class="card-sub">claims rejected</div>
            </div>

        </div>
    </div>

    {{-- ── STATUS BREAKDOWN ── --}}
    <div class="breakdown-section mt-16">
        <div class="section-heading">Claims by Status</div>
        <div class="breakdown-row">

            @foreach($breakdown_by_status as $status => $count)
                @php
                    $statusClass = match($status) {
                        'submitted'    => 'submitted',
                        'under_review' => 'review',
                        'approved'     => 'approved',
                        'rejected'     => 'rejected',
                        'paid'         => 'paid',
                        default        => 'submitted',
                    };
                    $statusLabel = match($status) {
                        'under_review' => 'Under Review',
                        default        => ucfirst($status),
                    };
                    $statusAmount = $claims->where('status', $status)->sum('claimed_amount');
                @endphp
                <div class="breakdown-cell">
                    <div class="breakdown-item {{ $statusClass }}">
                        <div class="breakdown-label">{{ $statusLabel }}</div>
                        <div class="breakdown-count">{{ $count }}</div>
                        <div class="breakdown-amount">KES {{ number_format($statusAmount, 2) }}</div>
                    </div>
                </div>
            @endforeach

        </div>
    </div>

    {{-- ── CLAIMS TABLE ── --}}
    <div class="section mt-20 pb-36">

        <div class="section-heading">Claim Details</div>

        @if($claims->isEmpty())

            <p style="color:#64748b;font-size:11px;padding:16px 0;">
                No claims found for {{ $month }}.
            </p>

        @else

            <table class="claims-table">

                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Claim #</th>
                        <th>Policy #</th>
                        <th>Patient</th>
                        <th class="right">Claimed</th>
                        <th class="right">Approved</th>
                        <th class="right">Deductible</th>
                        <th class="right">Payable</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($claims as $claim)
                        <tr>

                            <td>{{ \Carbon\Carbon::parse($claim->submitted_at)->format('M d, Y') }}</td>

                            <td style="color:#f97316;">
                                {{ $claim->claim_number ?? '—' }}
                            </td>

                            <td style="color:#3b82f6;">
                                {{ $claim->policy_number ?? '—' }}
                            </td>

                            <td>{{ $claim->patient->full_name ?? '—' }}</td>

                            <td class="right">
                                KES {{ number_format($claim->claimed_amount, 2) }}
                            </td>

                            <td class="right amount-positive">
                                {{ filled($claim->approved_amount) ? 'KES '.number_format($claim->approved_amount, 2) : '—' }}
                            </td>

                            <td class="right amount-muted">
                                KES {{ number_format($claim->deductible_amount ?? 0, 2) }}
                            </td>

                            <td class="right amount-positive">
                                KES {{ number_format($claim->net_amount ?? 0, 2) }}
                            </td>

                            <td>
                                <span class="badge badge-{{ $claim->status }}">
                                    {{ match($claim->status) {
                                        'under_review' => 'Under Review',
                                        default        => ucfirst($claim->status),
                                    } }}
                                </span>
                            </td>

                        </tr>
                    @endforeach

                </tbody>

                {{-- TOTALS ROW --}}
                <tfoot>
                    <tr class="total-row">
                        <td colspan="4" style="text-align:right;">TOTALS</td>
                        <td class="right">KES {{ number_format($summary['total_claimed'], 2) }}</td>
                        <td class="right">KES {{ number_format($summary['total_approved'], 2) }}</td>
                        <td class="right">KES {{ number_format($summary['total_deductible'], 2) }}</td>
                        <td class="right">KES {{ number_format($summary['total_payable'], 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>

            </table>

        @endif

    </div>

</div>

<div class="footer">
    <strong>{{ $provider->company_name ?? 'Insurance Provider' }}</strong> |
    This is a computer-generated statement and is valid without a physical signature. |
    Statement Period: {{ $month }} |
    Generated on {{ now()->format('F d, Y \a\t H:i') }}
</div>

</body>
</html>