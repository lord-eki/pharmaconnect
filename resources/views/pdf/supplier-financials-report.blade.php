<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Supplier Financials Report</title>

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

/* SUPPLIER BAND */

.supplier-band{
background:#f8fafc;
padding:18px 36px;
border-bottom:1px solid #e2e8f0;
}

.supplier-row{
display:table;
width:100%;
}

.supplier-left{
display:table-cell;
vertical-align:top;
width:55%;
}

.supplier-right{
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

.supplier-name{
font-size:15px;
font-weight:700;
color:#1a1a2e;
}

.supplier-detail{
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
width:33.33%;
background:#fff;
border:1px solid #e2e8f0;
border-top:3px solid #e2e8f0;
border-radius:6px;
padding:12px 14px;
}

.summary-card.orange{border-top-color:#f97316;}
.summary-card.green{border-top-color:#22c55e;}
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

.badge-completed{background:#dcfce7;color:#15803d;}
.badge-pending{background:#fef3c7;color:#b45309;}
.badge-failed{background:#fee2e2;color:#b91c1c;}

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
<div class="doc-title">SUPPLIER FINANCIALS REPORT</div>
<div class="doc-period">{{ $period_label }}</div>
<div class="doc-meta">
Generated: {{ $generated_at->format('F d, Y \a\t H:i') }}
</div>
</div>

</div>
</div>

<div class="accent-bar"></div>

<!-- SUPPLIER BAND -->

<div class="supplier-band">
<div class="supplier-row">

<div class="supplier-left">

<div class="label">Issued To</div>
<div class="supplier-name">{{ $supplier->name }}</div>

@if($supplier->email)
<div class="supplier-detail">{{ $supplier->email }}</div>
@endif

@if($supplier->phone)
<div class="supplier-detail">{{ $supplier->phone }}</div>
@endif

</div>

<div class="supplier-right">

<div class="label">Report Period</div>

<div class="supplier-name" style="font-size:13px;">
{{ $period_label }}
</div>

<div class="supplier-detail">
Total Transactions: {{ $payments->count() }}
</div>

</div>

</div>
</div>

<!-- SUMMARY -->

<div class="summary-section">
<div class="summary-row">

<div class="summary-card orange">
<div class="card-label">Total Payments</div>
<div class="card-value orange">
KES {{ number_format($totalAmount, 2) }}
</div>
<div class="card-sub">All completed payments</div>
</div>

<div class="summary-card green">
<div class="card-label">Months Covered</div>
<div class="card-value green">
{{ $byMonth->count() }}
</div>
<div class="card-sub">
{{ $month ? 'Single month' : 'Across '.$year }}
</div>
</div>

<div class="summary-card blue">
<div class="card-label">Avg. Per Month</div>
<div class="card-value blue">
KES {{ $byMonth->count() > 0 ? number_format($totalAmount / $byMonth->count(), 2) : '0.00' }}
</div>
<div class="card-sub">Based on active months</div>
</div>

</div>
</div>

<!-- MONTHLY BREAKDOWN -->

@if(!$month && $byMonth->isNotEmpty())
<div class="section mt-20">

<div class="section-heading">Monthly Breakdown</div>

<table class="month-table">
<thead>
<tr>
<th>Month</th>
<th>Transactions</th>
<th style="text-align:right;">Total Amount</th>
</tr>
</thead>
<tbody>

@foreach($byMonth as $monthLabel => $monthPayments)
<tr>
<td>{{ $monthLabel }}</td>
<td>{{ $monthPayments->count() }}</td>
<td style="text-align:right;" class="amount-positive">
KES {{ number_format($monthPayments->sum('amount'), 2) }}
</td>
</tr>
@endforeach

<tr class="total-row">
<td>Total</td>
<td>{{ $payments->count() }}</td>
<td style="text-align:right;">
KES {{ number_format($totalAmount, 2) }}
</td>
</tr>

</tbody>
</table>

</div>
@endif

<!-- TRANSACTION DETAILS -->

<div class="section mt-20 pb-36">

<div class="section-heading">Transaction Details</div>

@if($payments->isEmpty())

<p style="color:#64748b;font-size:11px;padding:16px 0;">
No payments found for this period.
</p>

@else

<table class="tx-table">

<thead>
<tr>
<th>Date</th>
<th>Reference</th>
<th>Description</th>
<th class="right">Amount</th>
<th>Status</th>
</tr>
</thead>

<tbody>

@foreach($payments as $payment)
<tr>

<td>{{ $payment->created_at->format('M d, Y') }}</td>

<td style="color:#f97316;">
{{ $payment->reference ?? '—' }}
</td>

<td style="color:#64748b;">
{{ $payment->description ?? '—' }}
</td>

<td class="right amount-positive">
KES {{ number_format($payment->amount, 2) }}
</td>

<td>
<span class="badge badge-{{ $payment->status }}">
{{ ucfirst($payment->status) }}
</span>
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
This is a computer-generated report and is valid without a physical signature. |
© {{ now()->year }} PharmaConnect. All rights reserved. |
Generated on {{ $generated_at->format('F d, Y \a\t H:i') }}

</div>

</body>
</html>