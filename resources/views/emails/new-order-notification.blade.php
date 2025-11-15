<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Order Notification</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; font-family:Arial, sans-serif;">

<div style="max-width:650px; margin:25px auto; background:#ffffff; border-radius:8px; padding:0; border:1px solid #e5e7eb;">

    <!-- Header -->
    <div style="background:#3b82f6; padding:20px; color:#ffffff; font-size:22px; font-weight:bold; border-radius:8px 8px 0 0; text-align:center;">
        New Order Received
    </div>

    <div style="padding:25px;">
        <p style="font-size:15px; color:#374151;">
            Hello Supplier,
        </p>

        <p style="font-size:15px; color:#374151;">
            A new order has been placed. Kindly review the details below.
        </p>

        <!-- Order Information -->
        <h3 style="font-size:18px; color:#111827; margin-top:25px; font-weight:bold;">
            Order Information
        </h3>

        <table style="width:100%; border-collapse:collapse; margin-top:10px;">
            <tr>
                <td style="padding:10px; font-weight:bold; color:#374151;">Order Number</td>
                <td style="padding:10px; color:#374151;">{{ $order->order_number }}</td>
            </tr>
            <tr style="background:#f9fafb;">
                <td style="padding:10px; font-weight:bold; color:#374151;">Order Date</td>
                <td style="padding:10px; color:#374151;">{{ $order->created_at->format('d M Y, h:i A') }}</td>
            </tr>
            <tr>
                <td style="padding:10px; font-weight:bold; color:#374151;">Total Amount</td>
                <td style="padding:10px; color:#374151;">KES {{ number_format($order->total_amount, 2) }}</td>
            </tr>
        </table>

        <!-- Items -->
        <h3 style="font-size:18px; color:#111827; margin-top:30px; font-weight:bold;">
            Order Items
        </h3>

        <table style="width:100%; border-collapse:collapse; margin-top:10px;">
            <thead>
                <tr style="background:#f3f4f6; text-align:left;">
                    <th style="padding:10px; font-size:14px; color:#374151;">Medicine</th>
                    <th style="padding:10px; font-size:14px; color:#374151;">Qty</th>
                    <th style="padding:10px; font-size:14px; color:#374151;">Unit Price</th>
                    <th style="padding:10px; font-size:14px; color:#374151;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($items as $item)
                <tr style="border-bottom:1px solid #e5e7eb;">
                    <td style="padding:10px; color:#374151;">{{ $item->medicine->name }}</td>
                    <td style="padding:10px; color:#374151;">{{ $item->quantity }}</td>
                    <td style="padding:10px; color:#374151;">KES {{ number_format($item->unit_price, 2) }}</td>
                    <td style="padding:10px; color:#374151;">KES {{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <!-- Patient -->
        <h3 style="font-size:18px; color:#111827; margin-top:30px; font-weight:bold;">
            Patient Information
        </h3>

        <table style="width:100%; border-collapse:collapse; margin-top:10px;">
            <tr>
                <td style="padding:10px; font-weight:bold; color:#374151;">Name</td>
                <td style="padding:10px; color:#374151;">
                    {{ $patient->first_name }} {{ $patient->last_name }}
                </td>
            </tr>
            <tr style="background:#f9fafb;">
                <td style="padding:10px; font-weight:bold; color:#374151;">Age</td>
                <td style="padding:10px; color:#374151;">{{ $patient->age }}</td>
            </tr>
            <tr>
                <td style="padding:10px; font-weight:bold; color:#374151;">Conditions</td>
                <td style="padding:10px; color:#374151;">{{ $patient->medical_conditions ?? 'None' }}</td>
            </tr>
            <tr style="background:#f9fafb;">
                <td style="padding:10px; font-weight:bold; color:#374151;">Allergies</td>
                <td style="padding:10px; color:#374151;">{{ $patient->allergies ?? 'None' }}</td>
            </tr>
        </table>

        <!-- Physician -->
        <h3 style="font-size:18px; color:#111827; margin-top:30px; font-weight:bold;">
            Prescribing Physician
        </h3>

        <table style="width:100%; border-collapse:collapse; margin-top:10px;">
            <tr>
                <td style="padding:10px; font-weight:bold; color:#374151;">Name</td>
                <td style="padding:10px; color:#374151;">
                    {{ $physician->first_name }} {{ $physician->last_name }}
                </td>
            </tr>
            <tr style="background:#f9fafb;">
                <td style="padding:10px; font-weight:bold; color:#374151;">Specialization</td>
                <td style="padding:10px; color:#374151;">{{ $physician->specialization }}</td>
            </tr>
            <tr>
                <td style="padding:10px; font-weight:bold; color:#374151;">Phone</td>
                <td style="padding:10px; color:#374151;">{{ $physician->phone }}</td>
            </tr>
        </table>

        <!-- Button -->
        <div style="text-align:center; margin-top:30px;">
            <a href="{{ url('/') }}"
               style="
                    background:#2563eb;
                    color:white;
                    padding:12px 22px;
                    text-decoration:none;
                    font-size:15px;
                    font-weight:bold;
                    border-radius:6px;
                ">
                View Full Order
            </a>
        </div>

        <!-- Footer -->
        <p style="text-align:center; margin-top:30px; font-size:13px; color:#6b7280;">
            © {{ date('Y') }} {{ config('app.name') }} — All rights reserved.
        </p>
    </div>

</div>

</body>
</html>
