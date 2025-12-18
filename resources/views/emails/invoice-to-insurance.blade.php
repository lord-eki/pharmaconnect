<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Invoice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #059669;
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            background-color: #ffffff;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .info-box {
            background-color: #f9fafb;
            border-left: 4px solid #059669;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box h3 {
            margin: 0 0 10px 0;
            color: #059669;
            font-size: 16px;
        }
        .info-box p {
            margin: 5px 0;
            font-size: 14px;
        }
        .info-box strong {
            color: #111;
        }
        .highlight {
            background-color: #ecfdf5;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #d1fae5;
        }
        .highlight p {
            margin: 8px 0;
            font-size: 14px;
        }
        .amount {
            font-size: 24px;
            font-weight: bold;
            color: #047857;
            text-align: center;
            padding: 20px;
            background-color: #ecfdf5;
            border-radius: 8px;
            margin: 20px 0;
        }
        .footer {
            background-color: #f3f4f6;
            padding: 20px;
            text-align: center;
            border-radius: 0 0 8px 8px;
            margin-top: 0;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .footer p {
            margin: 5px 0;
            font-size: 13px;
            color: #666;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #059669;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 15px 0;
        }
        .note {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .note p {
            margin: 5px 0;
            color: #78350f;
            font-size: 14px;
        }
        ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        li {
            margin: 5px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 Medical Invoice</h1>
        <p style="margin: 10px 0 0 0; font-size: 16px;">Pharmaconnect Healthcare Services</p>
    </div>

    <div class="content">
        <div class="greeting">
            <p>Dear {{ $insuranceProvider->contact_person ?? $insuranceProvider->company_name }},</p>
        </div>

        <p>
            We are writing to submit a medical invoice for reimbursement of pharmaceutical services provided to one of your insured members.
        </p>

        <div class="info-box">
            <h3>Invoice Details</h3>
            <p><strong>Invoice Number:</strong> {{ $invoice->invoice_number }}</p>
            <p><strong>Invoice Date:</strong> {{ $invoice->created_at->format('F d, Y') }}</p>
            <p><strong>Due Date:</strong> {{ $invoice->due_date ? $invoice->due_date->format('F d, Y') : 'Upon Receipt' }}</p>
            <p><strong>Order Reference:</strong> {{ $invoice->order->order_number }}</p>
        </div>

        <div class="info-box">
            <h3>Patient Information</h3>
            <p><strong>Patient Name:</strong> {{ $patient->full_name }}</p>
            @if($patient->patient_number)
            <p><strong>Patient ID:</strong> {{ $patient->patient_number }}</p>
            @endif
            @if($patient->insurance_policy_number)
            <p><strong>Policy Number:</strong> {{ $patient->insurance_policy_number }}</p>
            @endif
            @if($patient->insurance_member_id)
            <p><strong>Member ID:</strong> {{ $patient->insurance_member_id }}</p>
            @endif
        </div>

        <div class="amount">
            Total Amount: {{ $invoice->currency }} {{ number_format($invoice->total_amount, 2) }}
        </div>

        <div class="highlight">
            <p><strong>Invoice Summary:</strong></p>
            <p>• Subtotal: {{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</p>
            @if($invoice->discount_amount > 0)
            <p>• Discount: {{ $invoice->currency }} {{ number_format($invoice->discount_amount, 2) }}</p>
            @endif
            @if($invoice->tax_amount > 0)
            <p>• Tax (16% VAT): {{ $invoice->currency }} {{ number_format($invoice->tax_amount, 2) }}</p>
            @endif
            <p style="font-size: 16px; font-weight: 600; margin-top: 10px; color: #047857;">
                • Total Due: {{ $invoice->currency }} {{ number_format($invoice->total_amount, 2) }}
            </p>
        </div>

        @if($additionalMessage)
        <div class="note">
            <p><strong>Additional Information:</strong></p>
            <p>{{ $additionalMessage }}</p>
        </div>
        @endif

        <p style="margin-top: 25px;">
            <strong>Please find the detailed invoice attached to this email.</strong>
        </p>

        <p>
            The invoice includes:
        </p>
        <ul>
            <li>Complete itemized list of all medications dispensed</li>
            <li>Prescription details and prescribing physician information</li>
            <li>Patient information and insurance details</li>
            <li>Pricing breakdown including taxes</li>
        </ul>

        <div class="info-box" style="margin-top: 25px;">
            <h3>Payment Information</h3>
            <p><strong>Bank Name:</strong> Kenya Commercial Bank</p>
            <p><strong>Account Name:</strong> Pharmaconnect Limited</p>
            <p><strong>Account Number:</strong> 1234567890</p>
            <p><strong>Bank Code:</strong> 01-XXX</p>
            <p><strong>SWIFT Code:</strong> KCBLKENXXXX</p>
            <p style="margin-top: 10px; color: #059669; font-weight: 600;">
                Reference: {{ $invoice->invoice_number }}
            </p>
        </div>

        <p style="margin-top: 25px;">
            Should you require any additional documentation or have questions regarding this invoice, please do not hesitate to contact our billing department.
        </p>

        <p style="margin-top: 20px;">
            Thank you for your prompt attention to this matter.
        </p>

        <p style="margin-top: 30px;">
            Best regards,<br>
            <strong>Pharmaconnect Billing Department</strong><br>
            Email: billing@pharmaconnect.com<br>
            Phone: +254 700 000 000
        </p>
    </div>

    <div class="footer">
        <p><strong>Pharmaconnect Healthcare Services</strong></p>
        <p>P.O. Box 12345, Nairobi, Kenya</p>
        <p>Tel: +254 700 000 000 | Email: billing@pharmaconnect.com</p>
        <p>Website: www.pharmaconnect.com</p>
        <p style="margin-top: 15px; font-size: 12px; color: #888;">
            This is an automated email. Please do not reply directly to this message.<br>
            For billing inquiries, contact billing@pharmaconnect.com
        </p>
    </div>
</body>
</html>