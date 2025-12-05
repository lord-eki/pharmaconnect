<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Insurance Claim Form - {{ $claimForm->form_number }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 10px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            background-color: #f0f0f0;
            padding: 5px 10px;
            margin-bottom: 10px;
        }
        .field-row {
            display: flex;
            margin-bottom: 8px;
        }
        .field-label {
            width: 40%;
            font-weight: bold;
        }
        .field-value {
            width: 60%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #f0f0f0;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #333;
            font-size: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" alt="Logo" class="logo">
        @endif
        <div class="company-name">{{ $insuranceProvider->company_name }}</div>
        @if($insuranceProvider->form_header)
            <div style="margin-top: 10px;">{{ $insuranceProvider->form_header }}</div>
        @endif
        <h2>Medical Insurance Claim Form</h2>
        <div>Form Number: {{ $claimForm->form_number }}</div>
        <div>Date: {{ $generatedAt->format('d/m/Y') }}</div>
    </div>

    <div class="section">
        <div class="section-title">Patient Information</div>
        <div class="field-row">
            <div class="field-label">Patient Number:</div>
            <div class="field-value">{{ $patient->patient_number }}</div>
        </div>
        <div class="field-row">
            <div class="field-label">Patient Name:</div>
            <div class="field-value">{{ $patient->first_name }} {{ $patient->last_name }}</div>
        </div>
        <div class="field-row">
            <div class="field-label">Date of Birth:</div>
            <div class="field-value">{{ $patient->date_of_birth }}</div>
        </div>
        @foreach($claimForm->form_data ?? [] as $key => $value)
            <div class="field-row">
                <div class="field-label">{{ ucwords(str_replace('_', ' ', $key)) }}:</div>
                <div class="field-value">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="section">
        <div class="section-title">Physician Information</div>
        <div class="field-row">
            <div class="field-label">Physician Name:</div>
            <div class="field-value">{{ $physician->name }}</div>
        </div>
        <div class="field-row">
            <div class="field-label">License Number:</div>
            <div class="field-value">{{ $physician->userProfile->license_number ?? 'N/A' }}</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Clinical Information</div>
        <div class="field-row">
            <div class="field-label">Diagnosis:</div>
            <div class="field-value">{{ $claimForm->diagnosis }}</div>
        </div>
        @if($claimForm->treatment_notes)
        <div class="field-row">
            <div class="field-label">Treatment Notes:</div>
            <div class="field-value">{{ $claimForm->treatment_notes }}</div>
        </div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Prescribed Medications</div>
        <table>
            <thead>
                <tr>
                    <th>Medicine</th>
                    <th>Dosage</th>
                    <th>Quantity</th>
                    <th>Instructions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($prescription->items as $item)
                <tr>
                    <td>{{ $item->medicine->generic_name }}</td>
                    <td>{{ $item->medicine->strength }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->dosage_instructions }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($insuranceProvider->form_footer)
    <div class="footer">
        {{ $insuranceProvider->form_footer }}
    </div>
    @endif
</body>
</html>