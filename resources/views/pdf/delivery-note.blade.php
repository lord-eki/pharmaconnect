<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Confirmation</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    
    <div style="background-color: #2563eb; color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="margin: 0; font-size: 28px;">Delivery Confirmed!</h1>
        <p style="margin: 10px 0 0 0; font-size: 16px;">Your order has been successfully delivered</p>
    </div>

    <div style="background-color: #f9fafb; padding: 30px; border: 1px solid #e5e7eb;">
        
        <p style="font-size: 16px; margin-bottom: 20px;">
            Dear <strong>{{ $patient->full_name ?? 'Customer' }}</strong>,
        </p>

        <p style="font-size: 14px; margin-bottom: 20px;">
            We're pleased to confirm that your order has been successfully delivered. Please find the details below:
        </p>

        <div style="background-color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e5e7eb;">
            <h2 style="color: #2563eb; font-size: 18px; margin-top: 0;">Delivery Details</h2>
            
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; font-weight: bold; width: 40%;">Delivery Number:</td>
                    <td style="padding: 8px 0;">{{ $delivery->delivery_number }}</td>
                </tr>
                @if($prescription)
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Prescription Number:</td>
                    <td style="padding: 8px 0;">{{ $prescription->prescription_number }}</td>
                </tr>
                @endif
                @if($orders && $orders->count() > 0)
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Order Number(s):</td>
                    <td style="padding: 8px 0;">{{ $orders->pluck('order_number')->join(', ') }}</td>
                </tr>
                @endif
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Delivery Date:</td>
                    <td style="padding: 8px 0;">{{ $delivery->actual_delivery?->format('F d, Y \a\t H:i A') ?? ($delivery->delivered_at?->format('F d, Y \a\t H:i A') ?? 'N/A') }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Delivered By:</td>
                    <td style="padding: 8px 0;">{{ $rider?->full_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: bold;">Delivery Address:</td>
                    <td style="padding: 8px 0;">{{ $delivery->delivery_address }}</td>
                </tr>
            </table>
        </div>

        @if($orders && $orders->count() > 0)
        <div style="background-color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e5e7eb;">
            <h2 style="color: #2563eb; font-size: 18px; margin-top: 0;">Order Summary</h2>
            
            @foreach($orders as $order)
                @if($orders->count() > 1)
                <h3 style="color: #374151; font-size: 14px; margin: 15px 0 10px 0;">
                    Order: {{ $order->order_number }}
                    @if($order->supplier)
                    <span style="color: #6b7280; font-weight: normal;">({{ $order->supplier->name }})</span>
                    @endif
                </h3>
                @endif
                
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                    <thead>
                        <tr style="border-bottom: 2px solid #e5e7eb;">
                            <th style="padding: 10px 0; text-align: left;">Item</th>
                            <th style="padding: 10px 0; text-align: center;">Qty</th>
                            <th style="padding: 10px 0; text-align: right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 10px 0;">
                                {{ $item->medicine->generic_name ?? $item->product_name ?? 'N/A' }}
                                @if($item->medicine && $item->medicine->brand_name)
                                <br><span style="font-size: 12px; color: #6b7280;">{{ $item->medicine->brand_name }}</span>
                                @endif
                            </td>
                            <td style="padding: 10px 0; text-align: center;">{{ $item->quantity }}</td>
                            <td style="padding: 10px 0; text-align: right;">KES {{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                
                @if($orders->count() > 1 && !$loop->last)
                <hr style="border: none; border-top: 1px dashed #e5e7eb; margin: 15px 0;">
                @endif
            @endforeach

            <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                <tfoot>
                    <tr>
                        <td colspan="2" style="padding: 10px 0; text-align: right; font-weight: bold;">Subtotal:</td>
                        <td style="padding: 10px 0; text-align: right; font-weight: bold;">KES {{ number_format($orders->sum('total_amount'), 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding: 10px 0; text-align: right; font-weight: bold;">Delivery Fee:</td>
                        <td style="padding: 10px 0; text-align: right; font-weight: bold;">KES {{ number_format($delivery->delivery_fee, 2) }}</td>
                    </tr>
                    <tr style="border-top: 2px solid #2563eb;">
                        <td colspan="2" style="padding: 10px 0; text-align: right; font-weight: bold; font-size: 16px;">Total:</td>
                        <td style="padding: 10px 0; text-align: right; font-weight: bold; font-size: 16px; color: #2563eb;">KES {{ number_format($orders->sum('total_amount') + $delivery->delivery_fee, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif

        <div style="background-color: #dbeafe; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #2563eb;">
            <p style="margin: 0; font-size: 14px;">
                <strong>📋 Attached:</strong> Your detailed delivery note is attached to this email as a PDF document for your records.
            </p>
        </div>

        @if($delivery->delivery_notes)
        <div style="background-color: #fef3c7; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #f59e0b;">
            <p style="margin: 0; font-size: 14px;">
                <strong>📝 Delivery Notes:</strong> {{ $delivery->delivery_notes }}
            </p>
        </div>
        @endif

        <p style="font-size: 14px; margin-bottom: 20px;">
            If you have any questions or concerns about your delivery, please don't hesitate to contact us.
        </p>

        <p style="font-size: 14px; margin-bottom: 5px;">
            Thank you for choosing us!
        </p>

        <p style="font-size: 14px; color: #666; margin-bottom: 0;">
            Best regards,<br>
            <strong>{{ config('app.name') }} Team</strong>
        </p>

    </div>

    <div style="background-color: #f3f4f6; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; font-size: 12px; color: #666;">
        <p style="margin: 0 0 10px 0;">
            This is an automated email. Please do not reply to this message.
        </p>
        <p style="margin: 0;">
            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </p>
        @if($company_info ?? null)
        <p style="margin: 10px 0 0 0;">
            {{ $company_info['name'] ?? config('app.name') }}<br>
            @if(isset($company_info['address'])){{ $company_info['address'] }}<br>@endif
            @if(isset($company_info['phone']))Tel: {{ $company_info['phone'] }}<br>@endif
            @if(isset($company_info['email']))Email: {{ $company_info['email'] }}@endif
        </p>
        @endif
    </div>

</body>
</html>