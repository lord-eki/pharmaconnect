<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Commission Statement</title>

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

/* HEADER */

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

/* PHYSICIAN BAND */

.physician-band{
background:#f8fafc;
padding:18px 36px;
border-bottom:1px solid #e2e8f0;
}

.physician-row{
display:table;
width:100%;
}

.physician-left{
display:table-cell;
vertical-align:top;
width:55%;
}

.physician-right{
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

.physician-name{
font-size:15px;
font-weight:700;
color:#1a1a2e;
}

.physician-detail{
font-size:10px;
color:#64748b;
margin-top:2px;
}

/* SUMMARY */

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
width:25%;
background:#fff;
border:1px solid #e2e8f0;
border-top:3px solid #e2e8f0;
border-radius:6px;
padding:12px 14px;
}

.summary-card.orange{border-top-color:#f97316;}
.summary-card.green{border-top-color:#22c55e;}
.summary-card.amber{border-top-color:#f59e0b;}
.summary-card.blue{border-top-color:#3b82f6;}

.card-label{
font-size:9px;
color:#64748b;
text-transform:uppercase;
letter-spacing:0.8px;
}

.card-value{
font-size:14px;
font-weight:700;
margin-top:4px;
}

.card-value.orange{color:#f97316;}
.card-value.green{color:#16a34a;}
.card-value.amber{color:#d97706;}
.card-value.blue{color:#2563eb;}

.card-sub{
font-size:9px;
color:#94a3b8;
margin-top:2px;
}

/* SECTIONS */

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

/* MONTH TABLE */

.month-table{
width:100%;
border-collapse:collapse;
font-size:10.5px;
}

.month-table th{
background:#1a1a2e;
color:#fff;
padding:8px 10px;
text-align:left;
font-size:9px;
text-transform:uppercase;
}

.month-table td{
padding:8px 10px;
border-bottom:1px solid #f1f5f9;
}

.month-table tr:nth-child(even) td{
background:#f8fafc;
}

.month-table .total-row td{
background:#fef3e8;
font-weight:700;
border-top:2px solid #f97316;
}

/* TRANSACTION TABLE */

.tx-table{
width:100%;
border-collapse:collapse;
font-size:9.5px;
}

.tx-table th{
background:#1a1a2e;
color:#fff;
padding:7px 8px;
text-align:left;
font-size:8.5px;
text-transform:uppercase;
}

.tx-table td{
padding:6px 8px;
border-bottom:1px solid #f1f5f9;
}

.tx-table tr:nth-child(even) td{
background:#f8fafc;
}

.tx-table td.right,
.tx-table th.right{
text-align:right;
}

/* BADGES */

.badge{
display:inline-block;
padding:2px 7px;
border-radius:9px;
font-size:8px;
font-weight:700;
text-transform:uppercase;
}

.badge-pending{background:#fef3c7;color:#b45309;}
.badge-approved{background:#dbeafe;color:#1d4ed8;}
.badge-paid{background:#dcfce7;color:#15803d;}

.amount-positive{
color:#16a34a;
font-weight:700;
}

/* FOOTER (PDF SAFE) */

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

/* Optional page numbers */

.footer:after{
content:" | Page " counter(page) " of " counter(pages);
}

.mt-20{
margin-top:20px;
}

.pb-36{
padding-bottom:36px;
}

</style>
</head>

<body>

<div class="page-content">

<!-- HEADER -->

<div class="header">
<div class="header-row">

<div class="header-left">
<div class="brand">PharmaConnect</div>
<div class="brand-sub">123 Medical Street, Nairobi, Kenya</div>
<div class="brand-sub">
Tel: +254 XXX XXX XXX | info@pharmaconnect.com
</div>
</div>

<div class="header-right">
<div class="doc-title">COMMISSION STATEMENT</div>
<div class="doc-period">{{ $period_label }}</div>
<div class="doc-meta">
Generated: {{ $generated_at->format('F d, Y \a\t H:i') }}
</div>
</div>

</div>
</div>

<div class="accent-bar"></div>

<!-- PHYSICIAN -->

<div class="physician-band">
<div class="physician-row">

<div class="physician-left">

<div class="label">Issued To</div>
<div class="physician-name">{{ $physician->name }}</div>

@if($physician->email)
<div class="physician-detail">{{ $physician->email }}</div>
@endif

@if($physician->phone)
<div class="physician-detail">{{ $physician->phone }}</div>
@endif

</div>

<div class="physician-right">

<div class="label">Statement Period</div>

<div class="physician-name" style="font-size:13px;">
{{ $period_label }}
</div>

<div class="physician-detail">
Total Transactions: {{ $commissions->count() }}
</div>

</div>

</div>
</div>

<!-- SUMMARY -->

<div class="summary-section">
<div class="summary-row">

<div class="summary-card orange">
<div class="card-label">Total Commission</div>
<div class="card-value orange">
KES {{ number_format($totalCommission,2) }}
</div>
<div class="card-sub">All statuses</div>
</div>

<div class="summary-card green">
<div class="card-label">Paid Out</div>
<div class="card-value green">
KES {{ number_format($totalPaid,2) }}
</div>
<div class="card-sub">
{{ $commissions->where('status','paid')->count() }} transactions
</div>
</div>

<div class="summary-card blue">
<div class="card-label">Approved</div>
<div class="card-value blue">
KES {{ number_format($totalApproved,2) }}
</div>
<div class="card-sub">Awaiting payment</div>
</div>

<div class="summary-card amber">
<div class="card-label">Pending</div>
<div class="card-value amber">
KES {{ number_format($totalPending,2) }}
</div>
<div class="card-sub">Awaiting approval</div>
</div>

</div>
</div>

<!-- TRANSACTION DETAILS -->

<div class="section mt-20 pb-36">

<div class="section-heading">Transaction Details</div>

@if($commissions->isEmpty())

<p style="color:#64748b;font-size:11px;padding:16px 0;">
No commissions found for this period.
</p>

@else

<table class="tx-table">

<thead>
<tr>
<th>Date</th>
<th>Prescription #</th>
<th>Order #</th>
<th>Patient</th>
<th class="right">Order Amt</th>
<th class="right">Rate</th>
<th class="right">Commission</th>
<th>Status</th>
<th>Paid On</th>
</tr>
</thead>

<tbody>

@foreach($commissions as $commission)

<tr>

<td>{{ $commission->created_at->format('M d, Y') }}</td>

<td style="color:#f97316;">
{{ $commission->prescription->prescription_number ?? '—' }}
</td>

<td style="color:#3b82f6;">
{{ $commission->order->order_number ?? '—' }}
</td>

<td>
{{ $commission->prescription->patient->full_name ?? '—' }}
</td>

<td class="right">
KES {{ number_format($commission->gross_amount,2) }}
</td>

<td class="right">
{{ $commission->commission_rate }}%
</td>

<td class="right amount-positive">
KES {{ number_format($commission->commission_amount,2) }}
</td>

<td>
<span class="badge badge-{{ $commission->status }}">
{{ ucfirst($commission->status) }}
</span>
</td>

<td>
{{ $commission->paid_at ? $commission->paid_at->format('M d, Y') : '—' }}
</td>

</tr>

@endforeach

</tbody>

</table>

@endif

</div>

</div>

<!-- FOOTER -->

<div class="footer">

<strong>PharmaConnect</strong> |
This is a computer-generated statement and is valid without a physical signature. |
© {{ now()->year }} PharmaConnect. All rights reserved. |
Generated on {{ $generated_at->format('F d, Y \a\t H:i') }}

</div>

</body>
</html>