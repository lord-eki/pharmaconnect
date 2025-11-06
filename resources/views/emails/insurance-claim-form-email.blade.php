<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #1e40af; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        .info-box { background-color: white; padding: 15px; margin: 15px 0; border-left: 4px solid #1e40af; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Insurance Claim Form</h1>
            <p>{{ $claim->claim_number }}</p>
        </div>
        
        <div class="content">
            @if($customMessage)
            <p>{{ $customMessage }}</p>
            @else
            <p>Please find attached the insurance claim form for your review.</p>
            @endif

            <div class="info-box">
                <h3>Claim Details</h3>
                <p><strong>Claim Number:</strong> {{ $claim->claim_number }}</p>
                <p><strong>Patient:</strong> {{ $claim->patient->first_name }} {{ $claim->patient->last_name }}</p>
                <p><strong>Policy Number:</strong> {{ $claim->policy_number }}</p>
                <p><strong>Claimed Amount:</strong> KES {{ number_format($claim->claimed_amount, 2) }}</p>
                <p><strong>Submission Date:</strong> {{ $claim->submitted_at->format('F d, Y') }}</p>
                <p><strong>Status:</strong> {{ ucwords(str_replace('_', ' ', $claim->status)) }}</p>
            </div>

            <div class="info-box">
                <h3>Prescription Information</h3>
                <p><strong>Prescription Number:</strong> {{ $claim->prescription->prescription_number }}</p>
                <p><strong>Physician:</strong> {{ $claim->prescription->physician->name }}</p>
                <p><strong>Diagnosis:</strong> {{ $claim->prescription->diagnosis }}</p>
            </div>

            <p>The complete claim form with all details is attached to this email as a PDF document.</p>
        </div>

        <div class="footer">
            <p>This is an automated email from {{ config('app.name') }}</p>
            <p>{{ now()->format('F d, Y') }}</p>
        </div>
    </div>
</body>
</html>

{{-- resources/views/emails/new-insurance-claim.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #10b981; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        .alert { background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 15px 0; }
        .info-box { background-color: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .btn { display: inline-block; background-color: #10b981; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔔 New Insurance Claim Received</h1>
        </div>
        
        <div class="content">
            <div class="alert">
                <strong>Action Required:</strong> A new insurance claim has been submitted and requires your review.
            </div>

            <div class="info-box">
                <h3>Claim Information</h3>
                <table style="width: 100%;">
                    <tr>
                        <td style="padding: 5px 0;"><strong>Claim Number:</strong></td>
                        <td style="padding: 5px 0;">{{ $claim->claim_number }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0;"><strong>Submission Date:</strong></td>
                        <td style="padding: 5px 0;">{{ $claim->submitted_at->format('F d, Y H:i') }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0;"><strong>Status:</strong></td>
                        <td style="padding: 5px 0;">
                            <span style="background-color: #3b82f6; color: white; padding: 3px 10px; border-radius: 12px; font-size: 12px;">
                                {{ ucwords(str_replace('_', ' ', $claim->status)) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="info-box">
                <h3>Patient Details</h3>
                <p><strong>Name:</strong> {{ $claim->patient->first_name }} {{ $claim->patient->last_name }}</p>
                <p><strong>Patient Number:</strong> {{ $claim->patient->patient_number }}</p>
                <p><strong>Policy Number:</strong> {{ $claim->policy_number }}</p>
                <p><strong>Phone:</strong> {{ $claim->patient->phone }}</p>
            </div>

            <div class="info-box">
                <h3>Prescription Details</h3>
                <p><strong>Prescription Number:</strong> {{ $claim->prescription->prescription_number }}</p>
                <p><strong>Physician:</strong> {{ $claim->prescription->physician->name }}</p>
                <p><strong>Diagnosis:</strong> {{ $claim->prescription->diagnosis }}</p>
                <p><strong>Prescribed Date:</strong> {{ $claim->prescription->prescribed_at->format('F d, Y') }}</p>
            </div>

            <div class="info-box" style="background-color: #f0fdf4; border-left: 4px solid #10b981;">
                <h3 style="margin-top: 0;">Financial Summary</h3>
                <table style="width: 100%; font-size: 16px;">
                    <tr>
                        <td style="padding: 8px 0;"><strong>Claimed Amount:</strong></td>
                        <td style="padding: 8px 0; text-align: right;"><strong>KES {{ number_format($claim->claimed_amount, 2) }}</strong></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0;">Deductible:</td>
                        <td style="padding: 8px 0; text-align: right;">KES {{ number_format($claim->deductible_amount, 2) }}</td>
                    </tr>
                    <tr style="border-top: 2px solid #10b981;">
                        <td style="padding: 8px 0;"><strong>Net Claim:</strong></td>
                        <td style="padding: 8px 0; text-align: right; font-size: 18px; color: #10b981;">
                            <strong>KES {{ number_format($claim->claimed_amount - $claim->deductible_amount, 2) }}</strong>
                        </td>
                    </tr>
                </table>
            </div>

            <center>
                <a href="{{ url("/admin/insurance-claims/{$claim->id}") }}" class="btn">
                    Review Claim Now
                </a>
            </center>

            <p style="margin-top: 20px; font-size: 14px; color: #666;">
                Please log in to the system to review the full claim details, download the claim form, and process the claim.
            </p>
        </div>

        <div class="footer">
            <p>This is an automated notification from {{ config('app.name') }}</p>
            <p>{{ $claim->insuranceProvider->company_name }}</p>
            <p>{{ now()->format('F d, Y H:i') }}</p>
        </div>
    </div>
</body>
</html>