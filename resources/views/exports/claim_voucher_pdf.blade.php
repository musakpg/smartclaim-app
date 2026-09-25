<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Official Claim Voucher #CLM-{{ str_pad($claim->claim_id, 4, '0', STR_PAD_LEFT) }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #1e293b;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #1e293b;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #64748b;
        }
        .section-title {
            background-color: #f1f5f9;
            padding: 8px;
            font-weight: bold;
            margin-bottom: 10px;
            border-left: 4px solid #3b82f6;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #cbd5e1;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f8fafc;
            width: 30%;
        }
        .attachments {
            margin-top: 20px;
            text-align: center;
        }
        .attachments img {
            max-width: 45%;
            margin: 2%;
            border: 1px solid #cbd5e1;
            padding: 5px;
            display: inline-block;
            vertical-align: top;
        }
        .signature-block {
            margin-top: 40px;
        }
        .signature-block td {
            border: none;
            text-align: center;
            padding-top: 50px;
        }
        .signature-line {
            border-top: 1px solid #333;
            display: inline-block;
            width: 80%;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Aero Art Sdn Bhd</h1>
        <p>Official Expense Claim Voucher</p>
        <p>Voucher Ref: #CLM-{{ str_pad($claim->claim_id, 4, '0', STR_PAD_LEFT) }}</p>
    </div>

    <div class="section-title">Claimant Details</div>
    <table>
        <tr>
            <th>Staff Name</th>
            <td>{{ $claim->user->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Staff Email</th>
            <td>{{ $claim->user->email ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Submission Date</th>
            <td>{{ $claim->created_at->format('d M Y, h:i A') }}</td>
        </tr>
    </table>

    <div class="section-title">Expense Breakdown</div>
    <table>
        <tr>
            <th>Category</th>
            <td>{{ $claim->predicted_category ?? 'General' }}</td>
        </tr>
        <tr>
            <th>Merchant / Description</th>
            <td>{{ $claim->merchant_name ?? $claim->title ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Invoice Number</th>
            <td>{{ $claim->receipt_invoice_no ?? 'N/A' }}</td>
        </tr>
        @if($claim->claim_type === 'Mileage')
        <tr>
            <th>Distance</th>
            <td>{{ number_format($claim->mileage_km, 2) }} KM</td>
        </tr>
        @endif
        <tr>
            <th>Total Amount</th>
            <td><strong>RM {{ number_format($claim->amount, 2) }}</strong></td>
        </tr>
    </table>

    <div class="section-title">Audit Sign-off Trail</div>
    <table>
        @foreach($claim->auditLogs as $log)
            @if(in_array($log->action, ['CLAIM_PRE-APPROVED', 'CLAIM_APPROVED', 'PAYMENT_DISBURSED', 'PAYMENT_DISBURSED_BATCH']))
            <tr>
                <th>{{ str_replace('_', ' ', $log->action) }}</th>
                <td>
                    By: {{ $log->user->name ?? 'System' }}<br>
                    Date: {{ $log->created_at->format('d M Y, h:i A') }}
                </td>
            </tr>
            @endif
        @endforeach
    </table>

    <div class="section-title">Audit Attachments</div>
    <div class="attachments">
        @if($claim->receipt_path)
            @php
                $receiptFullPath = storage_path('app/public/' . $claim->receipt_path);
                $receiptBase64 = '';
                if(file_exists($receiptFullPath)) {
                    $type = pathinfo($receiptFullPath, PATHINFO_EXTENSION);
                    $data = file_get_contents($receiptFullPath);
                    $receiptBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                }
            @endphp
            @if($receiptBase64)
                <img src="{{ $receiptBase64 }}" alt="Original Receipt">
            @else
                <p>Receipt image not found on disk.</p>
            @endif
        @else
            <p>No receipt uploaded.</p>
        @endif

        @if($claim->proof_of_payment_path)
            @php
                $proofFullPath = storage_path('app/public/' . $claim->proof_of_payment_path);
                $proofBase64 = '';
                if(file_exists($proofFullPath)) {
                    $type = pathinfo($proofFullPath, PATHINFO_EXTENSION);
                    $data = file_get_contents($proofFullPath);
                    $proofBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                }
            @endphp
            @if($proofBase64)
                <img src="{{ $proofBase64 }}" alt="Proof of Payment">
            @else
                <p>Proof of payment image not found on disk.</p>
            @endif
        @endif
    </div>

    <table class="signature-block">
        <tr>
            <td>
                <div class="signature-line">
                    Verified By (Finance)
                </div>
            </td>
            <td>
                <div class="signature-line">
                    Approved By (Manager)
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
