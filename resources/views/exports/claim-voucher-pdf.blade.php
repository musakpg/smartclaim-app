<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>SmartClaim Payment Voucher - CLM-{{ $claim->claim_id }}</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0b1727">
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 11px;
            margin: 20px;
        }

        .header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .logo-title {
            font-size: 20px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }

        .sub-header {
            font-size: 9px;
            color: #64748b;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 4px;
        }

        .badge-approved {
            background-color: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .badge-rejected {
            background-color: #fff1f2;
            color: #be123c;
            border: 1px solid #fecdd3;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .meta-table td {
            padding: 6px;
            border: 1px solid #e2e8f0;
        }

        .meta-label {
            background-color: #f8fafc;
            font-weight: bold;
            width: 25%;
            color: #475569;
        }

        .breakdown-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            margin-bottom: 20px;
        }

        .breakdown-table th {
            background-color: #0f172a;
            color: #ffffff;
            padding: 8px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
        }

        .breakdown-table td {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
        }

        .total-row {
            font-size: 13px;
            font-weight: bold;
            background-color: #f8fafc;
        }

        .signatures {
            width: 100%;
            margin-top: 40px;
        }

        .sig-box {
            width: 45%;
            border-top: 1px dashed #94a3b8;
            text-align: center;
            padding-top: 5px;
            font-size: 10px;
            color: #64748b;
        }
    </style>
</head>

<body>

    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <div class="logo-title">SmartClaim Institutional Voucher</div>
                    <div class="sub-header">Aero Art Sdn Bhd &bull; Automated Expense Audit Desk</div>
                </td>
                <td style="text-align: right;">
                    <div style="font-size: 14px; font-weight: bold; font-family: monospace;">#CLM-{{ $claim->claim_id }}
                    </div>
                    <div style="margin-top: 4px;">
                        <span class="badge {{ $claim->status === 'Approved' ? 'badge-approved' : 'badge-rejected' }}">
                            {{ $claim->status }}
                        </span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Claimant Employee:</td>
            <td><strong>{{ $claim->user->name ?? 'Staff User' }}</strong> (ID: {{ $claim->user_id }})</td>
            <td class="meta-label">Disbursement Type:</td>
            <td>{{ $claim->claim_type }}</td>
        </tr>
        <tr>
            <td class="meta-label">Merchant / Venture:</td>
            <td><strong>{{ $claim->claim_type === 'Mileage' ? $claim->title : $claim->merchant_name }}</strong></td>
            <td class="meta-label">Invoice / Receipt No:</td>
            <td style="font-family: monospace;">{{ $claim->receipt_invoice_no }}</td>
        </tr>
        <tr>
            <td class="meta-label">Transaction Date:</td>
            <td>{{ $claim->transaction_date ? date('d F Y', strtotime($claim->transaction_date)) : 'N/A' }}</td>
            <td class="meta-label">Payment Method:</td>
            <td>{{ $claim->payment_method }}</td>
        </tr>
        <tr>
            <td class="meta-label">Predicted Category:</td>
            <td colspan="3"><strong>{{ $claim->predicted_category }}</strong></td>
        </tr>
        <tr>
            <td class="meta-label">Business Purpose:</td>
            <td colspan="3"><em>"{{ $claim->business_purpose }}"</em></td>
        </tr>
    </table>

    <table class="breakdown-table">
        <thead>
            <tr>
                <th>Audit Parameter / Particulars</th>
                <th style="text-align: center;">Reference Log</th>
                <th style="text-align: right;">Subtotal (MYR)</th>
            </tr>
        </thead>
        <tbody>
            @if($claim->claim_type === 'Mileage')
                <tr>
                    <td>Travel Allowance &bull; {{ $claim->start_location }} &rarr; {{ $claim->destination_location }}</td>
                    <td style="text-align: center; font-family: monospace;">{{ number_format($claim->mileage_km, 2) }} KM
                    </td>
                    <td style="text-align: right; font-weight: bold; font-family: monospace;">RM
                        {{ number_format($claim->amount, 2) }}</td>
                </tr>
            @else
                <tr>
                    <td>Direct Merchant Expenditure ({{ $claim->merchant_name }})</td>
                    <td style="text-align: center; font-family: monospace;">1 Receipt Attached</td>
                    <td style="text-align: right; font-weight: bold; font-family: monospace;">RM
                        {{ number_format($claim->amount, 2) }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td colspan="2" style="text-align: right; padding-right: 15px;">TOTAL AUTHORIZED DISBURSEMENT:</td>
                <td style="text-align: right; color: #047857; font-family: monospace;">RM
                    {{ number_format($claim->amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td class="sig-box">
                <strong>{{ $claim->user->name ?? 'Staff Claimant' }}</strong><br>
                Staff Signature / Submission Date: {{ $claim->created_at->format('d/m/Y') }}
            </td>
            <td style="width: 10%;"></td>
            <td class="sig-box">
                <strong>Executive Manager / Auditor</strong><br>
                Aero Art Management Sign-off / Date: {{ $claim->updated_at->format('d/m/Y') }}
            </td>
        </tr>
    </table>

</body>

</html>
