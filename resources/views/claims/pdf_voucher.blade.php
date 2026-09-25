<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Official Claim Voucher #CLM-{{ str_pad($claim->claim_id, 4, '0', STR_PAD_LEFT) }}</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0b1727">
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .brand-title {
            font-size: 20px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .brand-subtitle {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .voucher-title {
            font-size: 16px;
            font-weight: bold;
            text-align: right;
            color: #0284c7;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .meta-table td {
            vertical-align: top;
            padding: 4px 6px;
        }

        .meta-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px;
        }

        .meta-label {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 2px;
        }

        .meta-value {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        .items-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 9px;
            text-transform: uppercase;
            padding: 8px 10px;
            text-align: left;
        }

        .items-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        .items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total-box {
            width: 40%;
            margin-left: auto;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .total-box td {
            padding: 6px 10px;
        }

        .total-row {
            background-color: #f1f5f9;
            border-top: 2px solid #cbd5e1;
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
        }

        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 40px;
        }

        .signature-box {
            border-top: 1px dashed #94a3b8;
            padding-top: 6px;
            text-align: center;
            font-size: 9px;
            color: #475569;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-success {
            background-color: #dcfce7;
            color: #15803d;
        }

        .badge-info {
            background-color: #e0f2fe;
            color: #0369a1;
        }
    </style>
</head>

<body>

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td>
                <div class="brand-title">Aero Art Sdn Bhd</div>
                <div class="brand-subtitle">SmartClaim Automated Expenditure System</div>
            </td>
            <td>
                <div class="voucher-title">EXPENSE CLAIM VOUCHER</div>
                <div class="text-right" style="font-size: 10px; color: #64748b; font-family: monospace;">
                    #CLM-{{ str_pad($claim->claim_id, 4, '0', STR_PAD_LEFT) }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Claimant & Voucher Particulars -->
    <table class="meta-table">
        <tr>
            <td style="width: 50%;">
                <div class="meta-box">
                    <div class="meta-label">Claimant Particulars</div>
                    <div class="meta-value">{{ $claim->user->name ?? 'Staff Employee' }}</div>
                    <div style="font-size: 10px; color: #475569;">Email: {{ $claim->user->email ?? 'N/A' }}</div>
                    <div style="font-size: 10px; color: #475569; margin-top: 4px;">
                        Bank: <strong>{{ $claim->user->bank_name ?? 'N/A' }}</strong>
                        ({{ $claim->user->bank_account_no ?? 'No Account' }})
                    </div>
                </div>
            </td>
            <td style="width: 50%;">
                <div class="meta-box">
                    <div class="meta-label">Voucher Status & Category</div>
                    <div style="margin-bottom: 4px;">
                        <span class="badge {{ $claim->status === 'Reimbursed' ? 'badge-success' : 'badge-info' }}">
                            {{ $claim->status }}
                        </span>
                    </div>
                    <div style="font-size: 10px; color: #475569;">Type: <strong>{{ $claim->claim_type }}</strong></div>
                    <div style="font-size: 10px; color: #475569;">Category:
                        <strong>{{ $claim->predicted_category }}</strong></div>
                    <div style="font-size: 10px; color: #475569;">Date of Expense:
                        <strong>{{ $claim->transaction_date ? \Carbon\Carbon::parse($claim->transaction_date)->format('d M Y') : 'N/A' }}</strong>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Context & Business Purpose -->
    <div
        style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; margin-bottom: 20px;">
        <div class="meta-label">Business Purpose / Description</div>
        <div style="font-size: 11px; color: #334155;">{{ $claim->business_purpose }}</div>
        @if($claim->claim_type === 'Mileage')
            <div style="font-size: 10px; color: #0284c7; margin-top: 4px; font-weight: bold;">
                Route: {{ $claim->start_location }} &rarr; {{ $claim->destination_location }}
                ({{ number_format($claim->mileage_km, 2) }} KM via {{ $claim->vehicle_type ?? 'Vehicle' }})
            </div>
        @else
            <div style="font-size: 10px; color: #64748b; margin-top: 4px;">
                Merchant: <strong>{{ $claim->merchant_name }}</strong> | Invoice No:
                <strong>{{ $claim->receipt_invoice_no }}</strong>
            </div>
        @endif
    </div>

    <!-- Line Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 8%;">No</th>
                <th style="width: 52%;">Item Description</th>
                <th class="text-center" style="width: 12%;">Qty</th>
                <th class="text-right" style="width: 14%;">Unit Price (RM)</th>
                <th class="text-right" style="width: 14%;">Subtotal (RM)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($claim->items as $idx => $item)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>{{ $item->item_name }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td class="text-center">1</td>
                    <td>
                        {{ $claim->claim_type === 'Mileage' ? ($claim->title ?? 'Mileage Allowance Payout') : ($claim->merchant_name . ' Expense') }}
                    </td>
                    <td class="text-center">1</td>
                    <td class="text-right">{{ number_format($claim->amount, 2) }}</td>
                    <td class="text-right">{{ number_format($claim->amount, 2) }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Settlement Balance -->
    <table class="total-box">
        <tr class="total-row">
            <td>Authorized Payout</td>
            <td class="text-right">RM {{ number_format($claim->amount, 2) }}</td>
        </tr>
        @if($claim->payment_reference)
            <tr style="font-size: 9px; color: #059669;">
                <td>Bank Settlement Ref:</td>
                <td class="text-right" style="font-family: monospace;">{{ $claim->payment_reference }}</td>
            </tr>
        @endif
    </table>

    <!-- Digital Authorization Signatures -->
    <table class="signatures-table">
        <tr>
            <td style="width: 30%; padding: 0 15px;">
                <div class="signature-box">
                    <strong>{{ $claim->user->name ?? 'Staff Claimant' }}</strong><br>
                    <span>Claimant Signature</span>
                </div>
            </td>
            <td style="width: 30%; padding: 0 15px;">
                <div class="signature-box">
                    <strong>Operations Manager</strong><br>
                    <span>Verification & Approval</span>
                </div>
            </td>
            <td style="width: 30%; padding: 0 15px;">
                <div class="signature-box">
                    <strong>Finance & Treasury Desk</strong><br>
                    <span>Electronic Fund Settlement</span>
                </div>
            </td>
        </tr>
    </table>

    <div style="margin-top: 30px; font-size: 8px; color: #94a3b8; text-align: center;">
        Computer-generated document by SmartClaim System for Aero Art Sdn Bhd. Generated on
        {{ now()->format('d M Y, H:i') }}.
    </div>

</body>

</html>
